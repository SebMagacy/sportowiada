<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function isCatalogEmpty(): bool
    {
        return ! UserResource::getEloquentQuery()->exists();
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->isCatalogEmpty()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                View::make('filament.users.empty'),
            ]);
    }
}
