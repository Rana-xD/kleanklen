<?php

namespace Botble\ACL\Http\Requests;

use Botble\Base\Rules\EmailRule;
use Botble\Support\Http\Requests\Request;

class UpdateProfileRequest extends Request
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'alpha_dash', 'min:4', 'max:30'],
        ];
    }
}
