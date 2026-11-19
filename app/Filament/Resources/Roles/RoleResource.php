<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Actions\CatalogBulkActionGroup;
use App\Filament\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Tables\Table;

class RoleResource extends ShieldRoleResource
{
    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'index' => ListRoles::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table->toolbarActions([
            CatalogBulkActionGroup::make($table->getToolbarActions()),
        ]);
    }
}
