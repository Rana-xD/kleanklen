<?php

namespace Botble\Marketplace\Http\Requests\Fronts;

use Botble\Support\Http\Requests\Request;

class VendorEditWithdrawalRequest extends Request
{
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'max:400'],
        ];
    }
}
