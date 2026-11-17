<?php

namespace App\Filament\Resources\Leagues\Pages;

use App\Filament\Resources\Leagues\LeagueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeague extends EditRecord
{
    protected static string $resource = LeagueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('leagues.edit');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
