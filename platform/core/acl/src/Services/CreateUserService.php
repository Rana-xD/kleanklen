<?php

namespace Botble\ACL\Services;

use Botble\ACL\Events\RoleAssignmentEvent;
use Botble\ACL\Models\Role;
use Botble\ACL\Models\User;
use Botble\Support\Services\ProduceServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CreateUserService implements ProduceServiceInterface
{
    public function __construct(protected ActivateUserService $activateUserService)
    {
    }

    public function execute(Request $request): User
    {
        $user = new User();
        $user->fill($request->input());
        
        // Generate unique email if not provided
        $email = $request->input('email');
        if (empty($email)) {
            $username = $request->input('username', 'user');
            $timestamp = time();
            $email = $username . '_' . $timestamp . '@system.local';
            
            // Ensure email is unique by checking database
            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = $username . '_' . $timestamp . '_' . $counter . '@system.local';
                $counter++;
            }
        }
        
        $user->email = $email;
        $user->password = Hash::make($request->input('password'));
        $user->save();

        if (
            $this->activateUserService->activate($user) &&
            ($roleId = $request->input('role_id')) &&
            $role = Role::query()->find($roleId)
        ) {
            /**
             * @var Role $role
             */
            $role->users()->attach($user->getKey());

            event(new RoleAssignmentEvent($role, $user));
        }

        return $user;
    }
}
