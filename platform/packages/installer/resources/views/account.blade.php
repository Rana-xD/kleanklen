@extends('packages/installer::layouts.master')

@section(
    'pageTitle',
     trans(
         'packages/installer::installer.install_step_title',
         ['step' => 5, 'title' => trans('packages/installer::installer.createAccount.title')]
     )
)

@section('header')
    <x-core::card.title>
        {{ trans('packages/installer::installer.createAccount.title') }}
    </x-core::card.title>
@endsection

@section('content')
    <form
        id="create-account-form"
        method="post"
        action="{{ route('installers.accounts.createaccounts.store') }}"
    >
        @csrf
        <div class="row">
            <x-core::form.text-input
                wrapper-class="col-md-12"
                id="username"
                name="username"
                :label="trans('packages/installer::installer.createAccount.form.username')"
                :value="old('username')"
                :placeholder="trans('packages/installer::installer.createAccount.form.username')"
            />

            <x-core::form.text-input
                wrapper-class="col-md-6"
                id="password"
                name="password"
                type="password"
                :label="trans('packages/installer::installer.createAccount.form.password')"
                value="{{ old('password') }}"
                :placeholder="trans('packages/installer::installer.createAccount.form.password')"
            />

            <x-core::form.text-input
                wrapper-class="col-md-6"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                value="{{ old('password_confirmation') }}"
                :label="trans('packages/installer::installer.createAccount.form.password_confirmation')"
                :placeholder="trans('packages/installer::installer.createAccount.form.password_confirmation')"
            />
        </div>
    </form>

@endsection

@section('footer')
    <x-core::button
        color="primary"
        type="submit"
        form="create-account-form"
    >
        {{ trans('packages/installer::installer.createAccount.form.create') }}
    </x-core::button>
@endsection
