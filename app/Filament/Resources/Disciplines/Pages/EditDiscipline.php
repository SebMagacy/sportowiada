<?php

namespace App\Filament\Resources\Disciplines\Pages;

use App\Actions\DeleteDisciplines;
use App\Exceptions\DisciplineHasLeaguesException;
use App\Filament\Resources\Disciplines\DisciplineResource;
use App\Models\Discipline;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDiscipline extends EditRecord
{
    protected static string $resource = DisciplineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (
                    Discipline $record,
                    DeleteDisciplines $deleteDisciplines,
                    DeleteAction $action,
                ): bool {
                    try {
                        return $deleteDisciplines->handle([$record->id]) === 1;
                    } catch (DisciplineHasLeaguesException) {
                        Notification::make()
                            ->title(__('disciplines.delete_blocked_title'))
                            ->body(__('disciplines.delete_blocked_body'))
                            ->danger()
                            ->send();

                        $action->halt();

                        return false;
                    }
                }),
        ];
    }
}
