<?php

namespace App\Filament\Resources\Disciplines\Pages;

use App\Filament\Resources\Disciplines\DisciplineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListDisciplines extends ListRecords
{
    protected static string $resource = DisciplineResource::class;

    public function isCatalogEmpty(): bool
    {
        return ! DisciplineResource::getEloquentQuery()->exists();
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->isCatalogEmpty()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                View::make('filament.disciplines.empty')
                    ->viewData([
                        'canCreate' => DisciplineResource::canCreate(),
                        'createUrl' => DisciplineResource::getUrl('create'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('disciplines.create_title'))
                ->hidden(fn (): bool => $this->isCatalogEmpty()),
        ];
    }
}
