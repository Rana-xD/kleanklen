<?php

namespace Botble\ACL\Http\Controllers;

use Botble\ACL\Events\RoleAssignmentEvent;
use Botble\ACL\Events\RoleUpdateEvent;
use Botble\ACL\Forms\RoleForm;
use Botble\ACL\Http\Requests\AssignRoleRequest;
use Botble\ACL\Http\Requests\RoleCreateRequest;
use Botble\ACL\Models\Role;
use Botble\ACL\Models\User;
use Botble\ACL\Tables\RoleTable;
use Botble\Base\Http\Controllers\BaseSystemController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\Base\Supports\Helper;

/**
 * Controller for managing user roles and permissions
 * 
 * This controller handles all role-related operations including:
 * - Creating and editing roles
 * - Assigning permissions to roles
 * - Managing role assignments to users
 * - Role duplication and deletion
 */
class RoleController extends BaseSystemController
{
    /**
     * Generate breadcrumb for role management pages
     * @return Breadcrumb
     */
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(
                trans('core/acl::permissions.role_permission'),
                route('roles.index')
            );
    }

    /**
     * Display list of all roles
     * 
     * @param RoleTable $dataTable The role data table instance
     * @return mixed Returns the rendered role table view
     */
    public function index(RoleTable $dataTable)
    {
        $this->pageTitle(trans('core/acl::permissions.role_permission'));

        return $dataTable->renderTable();
    }

    /**
     * Delete a role
     * @param Role $role The role to delete
     * @return BaseHttpResponse
     */
    public function destroy(Role $role)
    {
        $role->delete();

        Helper::clearCache();

        return $this
            ->httpResponse()
            ->setMessage(trans('core/acl::permissions.delete_success'));
    }

    /**
     * Show the form for editing a role
     * 
     * @param Role $role The role to edit
     * @return mixed Returns the role edit form view
     */
    public function edit(Role $role)
    {
        $this->pageTitle(trans('core/acl::permissions.details', ['name' => $role->name]));

        return RoleForm::createFromModel($role)->renderForm();
    }

    /**
     * Update a role's details and permissions
     * 
     * @param Role $role The role to update
     * @param RoleCreateRequest $request The validated role update request
     * @return BaseHttpResponse
     */
    public function update(Role $role, RoleCreateRequest $request)
    {
        if ($request->input('is_default')) {
            Role::query()->where('id', '!=', $role->getKey())->update(['is_default' => 0]);
        }

        $role->name = $request->input('name');
        $role->permissions = $this->cleanPermission((array) $request->input('flags', []));
        $role->description = $request->input('description');
        $role->updated_by = $request->user()->getKey();
        $role->is_default = $request->input('is_default');
        $role->save();

        Helper::clearCache();

        event(new RoleUpdateEvent($role));

        return $this
            ->httpResponse()
            ->setPreviousRoute('roles.index')
            ->setNextRoute('roles.edit', $role->getKey())
            ->setMessage(trans('core/acl::permissions.modified_success'));
    }

    /**
     * Clean and format permission array
     * 
     * @param array $permissions Raw permission array
     * @return array Cleaned permission array with boolean values
     */
    protected function cleanPermission(array $permissions): array
    {
        if (! $permissions) {
            return [];
        }

        $cleanedPermissions = [];
        foreach ($permissions as $permissionName) {
            $cleanedPermissions[$permissionName] = true;
        }

        return $cleanedPermissions;
    }

    /**
     * Show form for creating a new role
     * 
     * @return mixed Returns the role creation form view
     */
    public function create()
    {
        $this->pageTitle(trans('core/acl::permissions.create_role'));

        return RoleForm::create()->renderForm();
    }

    /**
     * Store a newly created role
     * 
     * @param RoleCreateRequest $request The validated role creation request
     * @return BaseHttpResponse
     */
    public function store(RoleCreateRequest $request)
    {
        if ($request->input('is_default')) {
            Role::query()->update(['is_default' => 0]);
        }

        $role = Role::query()->create([
            'name' => $request->input('name'),
            'permissions' => $this->cleanPermission((array) $request->input('flags', [])),
            'description' => $request->input('description'),
            'is_default' => $request->input('is_default'),
            'created_by' => $request->user()->getKey(),
            'updated_by' => $request->user()->getKey(),
        ]);

        return $this
            ->httpResponse()
            ->setPreviousRoute('roles.index')
            ->setNextRoute('roles.edit', $role->getKey())
            ->setMessage(trans('core/acl::permissions.create_success'));
    }

    /**
     * Duplicate an existing role
     * 
     * @param Role $role The role to duplicate
     * @return BaseHttpResponse
     */
    public function getDuplicate(Role $role)
    {
        $duplicatedRole = Role::query()->create([
            'name' => $role->name . ' (Duplicate)',
            'slug' => $role->slug,
            'permissions' => $role->permissions,
            'description' => $role->description,
            'created_by' => $role->created_by,
            'updated_by' => $role->updated_by,
        ]);

        return $this->httpResponse()
            ->setPreviousRoute('roles.edit', $role->getKey())
            ->setNextRoute('roles.edit', $duplicatedRole->getKey())
            ->setMessage(trans('core/acl::permissions.duplicated_success'));
    }

    /**
     * Get JSON representation of all roles
     * 
     * @return array Array of role data for JSON response
     */
    public function getJson(): array
    {
        $pl = [];
        foreach (Role::query()->get() as $role) {
            $pl[] = [
                'value' => $role->getKey(),
                'text' => $role->name,
            ];
        }

        return $pl;
    }

    /**
     * Assign a role to a user
     * 
     * @param AssignRoleRequest $request The role assignment request
     * @return BaseHttpResponse
     */
    public function postAssignMember(AssignRoleRequest $request): BaseHttpResponse
    {
        /**
         * @var User $user
         */
        $user = User::query()->findOrFail($request->input('pk'));

        /**
         * @var Role $role
         */
        $role = Role::query()->findOrFail($request->input('value'));

        $user->roles()->sync([$role->getKey()]);

        event(new RoleAssignmentEvent($role, $user));

        return $this->httpResponse();
    }
}
