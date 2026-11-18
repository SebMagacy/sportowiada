<?php

namespace App\Filament\Resources\Leagues\Pages;

use App\Filament\Resources\Leagues\LeagueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListLeagues extends ListRecords
{
    protected static string $resource = LeagueResource::class;

    public function isCatalogEmpty(): bool
    {
        return ! LeagueResource::getEloquentQuery()->exists();
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->isCatalogEmpty()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                View::make('filament.leagues.empty')
                    ->viewData([
                        'canCreate' => LeagueResource::canCreate(),
                        'createUrl' => LeagueResource::getUrl('create'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('leagues.create'))
                ->hidden(fn (): bool => $this->isCatalogEmpty()),
        ];
    }
}
