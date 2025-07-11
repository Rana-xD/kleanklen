<?php

namespace Botble\ACL\Forms;

use Botble\ACL\Http\Requests\UpdateProfileRequest;
use Botble\ACL\Models\User;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;

class ProfileForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(User::class)
            ->template('core/base::forms.form-no-wrap')
            ->setFormOption('id', 'profile-form')
            ->setValidatorClass(UpdateProfileRequest::class)
            ->setMethod('PUT')
            ->columns()
            ->add(
                'username',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.username'))
                    ->required()
                    ->maxLength(30)
            )
            ->setActionButtons(view('core/acl::users.profile.actions')->render());
    }
}
