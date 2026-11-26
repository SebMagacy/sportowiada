<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): User
    {
        $user = User::create($data);

        $user->assignRole(Role::findOrCreate('user', 'web'));

        return $user;
    }

    protected function getNameFormComponent(): TextInput
    {
        return TextInput::make('name')
            ->label(__('filament-panels::auth/pages/register.form.name.label'))
            ->required()
            ->maxLength(255)
            ->autofocus()
            ->rule('string');
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        if ($component instanceof TextInput) {
            $component->helperText(__('auth.password_hint', ['min' => 15]));
        }

        return $component;
    }
}
