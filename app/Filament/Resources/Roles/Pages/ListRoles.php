<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles as ShieldListRoles;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListRoles extends ShieldListRoles
{
    protected static string $resource = RoleResource::class;

    public function isCatalogEmpty(): bool
    {
        return ! RoleResource::getEloquentQuery()->exists();
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->isCatalogEmpty()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                View::make('filament.roles.empty')
                    ->viewData([
                        'canCreate' => RoleResource::canCreate(),
                        'createUrl' => RoleResource::getUrl('create'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('roles.create_title'))
                ->hidden(fn (): bool => $this->isCatalogEmpty()),
        ];
    }
}
