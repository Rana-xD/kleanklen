<?php

namespace Botble\Ecommerce\Forms\Fronts\Auth;

use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Ecommerce\Forms\Fronts\Auth\FieldOptions\TextFieldOption;

class PhonePasswordResetForm extends AuthForm
{
    public static function formTitle(): string
    {
        return __('Customer phone password reset form');
    }

    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl('#')
            ->icon('ti ti-phone')
            ->heading(__('Reset Password via Phone'))
            ->description(__('Enter your phone number to receive a verification code.'))
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Phone Number'))
                    ->placeholder(__('093 123 321'))
                    ->icon('ti ti-phone')
                    ->helperText(__('Enter your Cambodia phone number (e.g., 093 123 321)'))
                    ->addAttribute('type', 'tel')
            )
            ->add('recaptcha_container', HtmlField::class, [
                'html' => '<div id="recaptcha-container" class="mb-3"></div>',
            ])
            ->add('submit_button', HtmlField::class, [
                'html' => '<div class="d-grid"><button type="button" class="btn btn-primary btn-auth-submit" id="send-otp-btn" onclick="sendOTP()" disabled>' . __('Send Verification Code') . '</button></div>',
            ])
            ->add('back_to_login', HtmlField::class, [
                'html' => sprintf(
                    '<div class="mt-3 text-center"><a href="%s" class="text-decoration-underline">%s</a></div>',
                    route('customer.login'),
                    __('Back to login page')
                ),
            ]);
    }
}
