<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AssignUserRolesAction
{
    public static function make(): Action
    {
        return Action::make('assignRoles')
            ->label('Role')
            ->modalHeading('Przypisz role użytkownikowi')
            ->modalSubmitActionLabel('Zapisz')
            ->authorize('assignRoles')
            ->fillForm(fn (User $record): array => [
                'roles' => $record->roles()->pluck('name')->all(),
            ])
            ->schema([
                Select::make('roles')
                    ->label('Role')
                    ->multiple()
                    ->options(fn (): array => Role::query()
                        ->where('guard_name', 'web')
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->all()),
            ])
            ->action(function (array $data, User $record): void {
                Gate::authorize('assignRoles', $record);

                $record->syncRoles($data['roles'] ?? []);

                Notification::make()
                    ->title('Role użytkownika zostały zapisane')
                    ->success()
                    ->send();
            });
    }
}
