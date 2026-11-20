<?php

namespace App\Filament\Resources\Disciplines;

use App\Actions\DeleteDisciplines;
use App\Exceptions\DisciplineHasLeaguesException;
use App\Filament\Actions\CatalogBulkActionGroup;
use App\Filament\Resources\Disciplines\Pages\CreateDiscipline;
use App\Filament\Resources\Disciplines\Pages\EditDiscipline;
use App\Filament\Resources\Disciplines\Pages\ListDisciplines;
use App\Models\Discipline;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class DisciplineResource extends Resource
{
    protected static ?string $model = Discipline::class;

    protected static string|BackedEnum|null $navigationIcon = 'sportowiada-disciplines';

    protected static string|UnitEnum|null $navigationGroup = 'Sportowiada';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPluralModelLabel(): string
    {
        return __('disciplines.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('disciplines.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('alias')
                    ->label(__('disciplines.alias'))
                    ->required()
                    ->maxLength(255)
                    ->regex('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/')
                    ->unique()
                    ->helperText(__('disciplines.alias_help')),

                RichEditor::make('description')
                    ->label(__('disciplines.description'))
                    ->fileAttachments(false)
                    ->dehydrateStateUsing(function (?string $state): ?string {
                        $text = html_entity_decode(
                            strip_tags($state ?? ''),
                            ENT_QUOTES | ENT_HTML5,
                            'UTF-8',
                        );

                        return blank(str_replace("\u{00A0}", ' ', $text))
                            ? null
                            : $state;
                    })
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),

                SpatieMediaLibraryFileUpload::make('icon')
                    ->label(__('disciplines.icon'))
                    ->collection('icon')
                    ->disk('public')
                    ->image()
                    ->imagePreviewHeight('150')
                    ->extraAttributes(['class' => 'discipline-icon-upload'])
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(2048),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('icon')
                    ->label('')
                    ->collection('icon')
                    ->visibility('public')
                    ->imageSize(40)
                    ->width('1%'),
                TextColumn::make('name')
                    ->label(__('disciplines.name'))
                    ->searchable(),
                TextColumn::make('alias')
                    ->label(__('disciplines.alias')),
                TextColumn::make('id')
                    ->label('ID'),
            ])
            ->defaultSort('id')

            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CatalogBulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (
                            DeleteBulkAction $action,
                            Collection $records,
                            DeleteDisciplines $deleteDisciplines,
                        ): void {
                            try {
                                $deletedCount = $deleteDisciplines->handle(
                                    $records->modelKeys(),
                                );

                                $action->reportBulkProcessingSuccessfulRecordsCount(
                                    $deletedCount,
                                );
                            } catch (DisciplineHasLeaguesException) {
                                Notification::make()
                                    ->title(__('disciplines.bulk_delete_blocked_title'))
                                    ->body(__('disciplines.bulk_delete_blocked_body'))
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisciplines::route('/'),
            'create' => CreateDiscipline::route('/create'),
            'edit' => EditDiscipline::route('/{record}/edit'),
        ];
    }
}
