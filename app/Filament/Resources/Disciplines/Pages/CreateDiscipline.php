<?php

namespace App\Filament\Resources\Disciplines\Pages;

use App\Filament\Resources\Disciplines\DisciplineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscipline extends CreateRecord
{
    protected static string $resource = DisciplineResource::class;

    public function getTitle(): string
    {
        return __('disciplines.create_title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
