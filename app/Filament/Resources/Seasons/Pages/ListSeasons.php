<?php

namespace App\Filament\Resources\Seasons\Pages;

use App\Filament\Resources\Seasons\SeasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListSeasons extends ListRecords
{
    protected static string $resource = SeasonResource::class;

    public function isCatalogEmpty(): bool
    {
        return ! SeasonResource::getEloquentQuery()->exists();
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->isCatalogEmpty()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                View::make('filament.seasons.empty')
                    ->viewData([
                        'canCreate' => SeasonResource::canCreate(),
                        'createUrl' => SeasonResource::getUrl('create'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
