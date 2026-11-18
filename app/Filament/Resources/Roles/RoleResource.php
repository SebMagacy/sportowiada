<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;

class RoleResource extends ShieldRoleResource
{
    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'index' => ListRoles::route('/'),
        ];
    }
}
