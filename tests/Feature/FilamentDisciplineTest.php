<?php

namespace Tests\Feature;

use App\Filament\Resources\Disciplines\DisciplineResource;
use App\Filament\Resources\Disciplines\Pages\CreateDiscipline;
use App\Filament\Resources\Disciplines\Pages\EditDiscipline;
use App\Filament\Resources\Disciplines\Pages\ListDisciplines;
use App\Models\Discipline;
use App\Models\League;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentDisciplineTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('panel');
    }

    public function test_empty_catalog_displays_guidance_for_creating_first_discipline(): void
    {
        $this->signInAdmin();
        app()->setLocale('pl');

        Livewire::test(ListDisciplines::class)
            ->assertOk()
            ->assertActionHidden('create')
            ->assertSeeText('Dodaj dyscyplinę')
            ->assertSeeText('Nie utworzono jeszcze żadnej dyscypliny.')
            ->assertSeeText(
                'Dyscyplina określa rodzaj sportu, do którego należą ligi. Dodaj pierwszą dyscyplinę, aby rozpocząć tworzenie katalogu sportowego.',
            );
    }

    public function test_english_empty_catalog_links_to_creation_and_changes_to_table_after_first_record(): void
    {
        $this->actingAs($this->createPanelAdministrator());
        app()->setLocale('en');

        $page = Livewire::test(ListDisciplines::class)
            ->assertOk()
            ->assertActionHidden('create')
            ->assertSeeText('No sports disciplines have been created yet.')
            ->assertSeeText('A discipline identifies the sport to which leagues belong. Add your first discipline to start building the sports catalog.')
            ->assertSeeText('Add discipline')
            ->assertSee('href="'.DisciplineResource::getUrl('create').'"', false);

        $record = Discipline::factory()->create();

        $page->refresh()
            ->assertOk()
            ->assertCanSeeTableRecords([$record])
            ->assertActionVisible('create')
            ->assertDontSeeText('No sports disciplines have been created yet.');
    }

    public function test_read_only_user_has_no_create_link_in_empty_catalog_or_table(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Discipline', 'web'));
        $this->actingAs($user);
        app()->setLocale('en');
        $createUrl = DisciplineResource::getUrl('create');

        Livewire::test(ListDisciplines::class)
            ->assertOk()
            ->assertSeeText('No sports disciplines have been created yet.')
            ->assertActionHidden('create')
            ->assertDontSee('href="'.$createUrl.'"', false);

        $record = Discipline::factory()->create();

        Livewire::test(ListDisciplines::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$record])
            ->assertActionHidden('create')
            ->assertDontSee('href="'.$createUrl.'"', false);

        $this->get($createUrl)->assertForbidden();
    }

    public function test_search_without_matches_does_not_display_empty_catalog_guidance(): void
    {
        $this->signInAdmin();
        app()->setLocale('pl');
        $discipline = Discipline::factory()->create(['name' => 'Siatkówka']);

        Livewire::test(ListDisciplines::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$discipline])
            ->assertDontSeeText('Nie utworzono jeszcze żadnej dyscypliny.')
            ->searchTable('zzzzbrakdopasowania')
            ->assertOk()
            ->assertCanNotSeeTableRecords([$discipline])
            ->assertDontSeeText('Nie utworzono jeszcze żadnej dyscypliny.')
            ->searchTable('')
            ->assertOk()
            ->assertCanSeeTableRecords([$discipline])
            ->assertDontSeeText('Nie utworzono jeszcze żadnej dyscypliny.');
    }

    public function test_guest_cannot_open_any_discipline_page(): void
    {
        $discipline = Discipline::factory()->create();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(DisciplineResource::getUrl($page, ['record' => $discipline]))
                ->assertRedirect(route('filament.panel.auth.login'));
        }
    }

    public function test_regular_user_cannot_open_any_discipline_page(): void
    {
        $discipline = Discipline::factory()->create();
        $this->actingAs(User::factory()->create());

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(DisciplineResource::getUrl($page, ['record' => $discipline]))->assertForbidden();
        }
    }

    public function test_admin_can_open_all_discipline_pages(): void
    {
        $discipline = Discipline::factory()->create();
        $this->signInAdmin();

        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(DisciplineResource::getUrl($page, ['record' => $discipline]))->assertOk();
        }
    }

    public function test_list_sorts_by_id_and_searches_names(): void
    {
        $this->signInAdmin();
        $first = Discipline::factory()->create(['name' => 'Siatkówka']);
        $second = Discipline::factory()->create(['name' => 'Koszykówka']);

        Livewire::test(ListDisciplines::class)
            ->assertCanSeeTableRecords([$first, $second], inOrder: true)
            ->searchTable('Siatkówka')
            ->assertCanSeeTableRecords([$first])
            ->assertCanNotSeeTableRecords([$second]);
    }

    public function test_admin_can_create_without_optional_fields(): void
    {
        $this->signInAdmin();

        Livewire::test(CreateDiscipline::class)
            ->fillForm(['name' => 'Piłka nożna', 'alias' => 'pilka-nozna'])
            ->call('create')->assertHasNoFormErrors()
            ->assertRedirect(DisciplineResource::getUrl('index'));

        $this->assertDatabaseHas('disciplines', ['name' => 'Piłka nożna', 'alias' => 'pilka-nozna', 'description' => null]);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_admin_can_create_with_multiline_description_and_image(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        $description = '<p>Zażółć gęślą jaźń.</p><p>Drugi wiersz.</p>';

        Livewire::test(CreateDiscipline::class)
            ->fillForm([
                'name' => 'Piłka nożna',
                'alias' => 'pilka-nozna',
                'description' => $description,
                'icon' => [UploadedFile::fake()->image('football.png')],
            ])
            ->call('create')->assertHasNoFormErrors();

        $discipline = Discipline::query()->sole();
        $this->assertSame($description, $discipline->description);
        $media = $discipline->getFirstMedia('icon');
        $this->assertNotNull($media);
        $this->assertSame('public', $media->disk);
        $disk->assertExists($media->getPathRelativeToRoot());
        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->assertFormSet(['description' => $description]);
    }

    /** @return array<string, array{array<string, mixed>, array<string, string>}> */
    public static function invalidData(): array
    {
        return [
            'required fields' => [['name' => '', 'alias' => ''], ['name' => 'required', 'alias' => 'required']],
            'long name' => [['name' => str_repeat('a', 256)], ['name' => 'max']],
            'long alias' => [['alias' => str_repeat('a', 256)], ['alias' => 'max']],
            'uppercase and accents' => [['alias' => 'Piłka'], ['alias' => 'regex']],
            'space' => [['alias' => 'pilka nozna'], ['alias' => 'regex']],
            'double dash' => [['alias' => 'pilka--nozna'], ['alias' => 'regex']],
            'leading dash' => [['alias' => '-pilka'], ['alias' => 'regex']],
            'trailing dash' => [['alias' => 'pilka-'], ['alias' => 'regex']],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_invalid_creation_does_not_save(array $changes, array $errors): void
    {
        $this->signInAdmin();

        Livewire::test(CreateDiscipline::class)
            ->fillForm(array_replace(['name' => 'Piłka nożna', 'alias' => 'pilka-nozna'], $changes))
            ->call('create')->assertHasFormErrors($errors)->assertNoRedirect();

        $this->assertDatabaseCount('disciplines', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_duplicate_alias_is_rejected_on_create_and_edit(): void
    {
        $this->signInAdmin();
        $existing = Discipline::factory()->create(['alias' => 'pilka-nozna']);
        $edited = Discipline::factory()->create();
        $originalAlias = $edited->alias;

        Livewire::test(CreateDiscipline::class)
            ->fillForm(['name' => 'Duplikat', 'alias' => $existing->alias])
            ->call('create')->assertHasFormErrors(['alias' => 'unique']);
        Livewire::test(EditDiscipline::class, ['record' => $edited->getRouteKey()])
            ->fillForm(['alias' => $existing->alias])
            ->call('save')->assertHasFormErrors(['alias' => 'unique']);

        $this->assertDatabaseCount('disciplines', 2);
        $this->assertSame($originalAlias, $edited->fresh()->alias);
    }

    public function test_edit_preserves_own_alias_and_can_clear_description(): void
    {
        $this->signInAdmin();
        $discipline = Discipline::factory()->create(['description' => 'Stary opis']);
        $alias = $discipline->alias;

        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->fillForm(['name' => 'Nowa nazwa', 'description' => null])
            ->call('save')->assertHasNoFormErrors();

        $this->assertDatabaseHas('disciplines', ['id' => $discipline->id, 'name' => 'Nowa nazwa', 'alias' => $alias, 'description' => null]);
    }

    public function test_edit_replaces_image_and_removes_old_file(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        $discipline = Discipline::factory()->create();
        $old = $discipline->addMedia(UploadedFile::fake()->image('old.png'))->toMediaCollection('icon', 'public');
        $oldPath = $old->getPathRelativeToRoot();

        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->fillForm(['icon' => [UploadedFile::fake()->image('new.png')]])
            ->call('save')->assertHasNoFormErrors();

        $media = $discipline->fresh()->getMedia('icon');
        $this->assertCount(1, $media);
        $this->assertNotSame($old->id, $media->first()->id);
        $this->assertSame('image/png', $media->first()->mime_type);
        $disk->assertMissing($oldPath);
        $disk->assertExists($media->first()->getPathRelativeToRoot());
    }

    public function test_edit_can_remove_image(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        $discipline = Discipline::factory()->create();
        $media = $discipline->addMedia(UploadedFile::fake()->image('old.png'))->toMediaCollection('icon', 'public');
        $path = $media->getPathRelativeToRoot();

        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->fillForm(['icon' => []])->call('save')->assertHasNoFormErrors();

        $this->assertDatabaseCount('media', 0);
        $this->assertModelExists($discipline);
        $disk->assertMissing($path);
    }

    public function test_single_file_collection_replaces_previous_image(): void
    {
        $disk = Storage::fake('public');
        $discipline = Discipline::factory()->create();
        $old = $discipline->addMedia(UploadedFile::fake()->image('old.png'))->toMediaCollection('icon', 'public');
        $oldPath = $old->getPathRelativeToRoot();

        $new = $discipline->addMedia(UploadedFile::fake()->image('new.png'))->toMediaCollection('icon', 'public');

        $this->assertCount(1, $discipline->fresh()->getMedia('icon'));
        $disk->assertMissing($oldPath);
        $disk->assertExists($new->getPathRelativeToRoot());
    }

    public function test_delete_removes_record_media_and_file(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        $discipline = Discipline::factory()->create();
        $media = $discipline->addMedia(UploadedFile::fake()->image('old.png'))->toMediaCollection('icon', 'public');
        $path = $media->getPathRelativeToRoot();

        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->callAction('delete')->assertHasNoErrors();

        $this->assertModelMissing($discipline);
        $this->assertDatabaseCount('media', 0);
        $disk->assertMissing($path);
    }

    public function test_bulk_delete_removes_only_selected_records(): void
    {
        $this->signInAdmin();
        $selected = Discipline::factory()->count(2)->create();
        $remaining = Discipline::factory()->create();

        Livewire::test(ListDisciplines::class)
            ->selectTableRecords($selected->modelKeys())
            ->callAction(TestAction::make('delete')->table()->bulk());

        foreach ($selected as $discipline) {
            $this->assertModelMissing($discipline);
        }
        $this->assertModelExists($remaining);
    }

    public function test_non_image_upload_is_rejected_without_saving(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();

        Livewire::test(CreateDiscipline::class)
            ->fillForm([
                'name' => 'Tenis',
                'alias' => 'tenis',
                'icon' => [UploadedFile::fake()->create('document.pdf', 20, 'application/pdf')],
            ])
            ->call('create')->assertHasFormErrors();

        $this->assertDatabaseCount('disciplines', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], $disk->allFiles());
    }

    public function test_oversized_image_is_rejected_without_saving(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();

        Livewire::test(CreateDiscipline::class)
            ->fillForm([
                'name' => 'Tenis',
                'alias' => 'tenis',
                'icon' => [UploadedFile::fake()->image('large.png')->size(2049)],
            ])
            ->call('create')->assertHasFormErrors();

        $this->assertDatabaseCount('disciplines', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], $disk->allFiles());
    }

    private function signInAdmin(): void
    {
        $this->actingAs($this->createPanelAdministrator());
    }

    public function test_read_only_user_cannot_invoke_bulk_deletion(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Discipline', 'web'));
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');
        $record = Discipline::factory()->create();
        $action = TestAction::make('delete')->table()->bulk();

        Livewire::test(ListDisciplines::class)
            ->assertOk()
            ->assertActionHidden($action)
            ->selectTableRecords([$record->getKey()])
            ->mountAction($action)
            ->assertActionNotMounted();

        $this->assertModelExists($record);
    }

    public function test_user_with_bulk_delete_permissions_can_delete_selected_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        foreach (['ViewAny', 'DeleteAny', 'Delete'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission.':Discipline', 'web'));
        }
        $this->actingAs($user);
        Filament::setCurrentPanel('panel');
        $record = Discipline::factory()->create();

        Livewire::test(ListDisciplines::class)
            ->assertActionHidden('create')
            ->assertActionVisible(TestAction::make('delete')->table()->bulk())
            ->selectTableRecords([$record->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoErrors();

        $this->assertModelMissing($record);
    }

    public function test_delete_preserves_discipline_with_league_and_media(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        app()->setLocale('pl');

        $discipline = Discipline::factory()->create();
        $league = League::factory()->for($discipline)->create();
        $media = $discipline
            ->addMedia(UploadedFile::fake()->image('icon.png'))
            ->toMediaCollection('icon', 'public');
        $path = $media->getPathRelativeToRoot();

        Livewire::test(EditDiscipline::class, [
            'record' => $discipline->getRouteKey(),
        ])
            ->callAction('delete')
            ->assertHasNoErrors()
            ->assertNotified(
                Notification::make()
                    ->title('Nie można usunąć dyscypliny')
                    ->body('Dyscyplina jest używana przez ligi.')
                    ->danger(),
            );

        $this->assertModelExists($discipline);
        $this->assertModelExists($league);
        $this->assertModelExists($media);
        $disk->assertExists($path);
    }

    /** @return array<string, array{string, string, string}> */
    public static function deletionMessages(): array
    {
        return [
            'Polish' => ['pl', 'Nie można usunąć zaznaczonych dyscyplin', 'Co najmniej jedna zaznaczona dyscyplina jest używana przez ligi. Nie usunięto żadnej dyscypliny.'],
            'English' => ['en', 'Cannot delete selected disciplines', 'At least one selected discipline is used by leagues. No disciplines were deleted.'],
        ];
    }

    #[DataProvider('deletionMessages')]
    public function test_bulk_delete_preserves_mixed_selection_and_media(
        string $locale,
        string $bulkTitle,
        string $bulkBody,
    ): void {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        app()->setLocale($locale);
        $unlinked = Discipline::factory()->create();
        $linked = Discipline::factory()->create();
        $league = League::factory()->for($linked)->create();
        $unlinkedMedia = $unlinked->addMedia(UploadedFile::fake()->image('unlinked.png'))->toMediaCollection('icon', 'public');
        $linkedMedia = $linked->addMedia(UploadedFile::fake()->image('linked.png'))->toMediaCollection('icon', 'public');

        Livewire::test(ListDisciplines::class)
            ->selectTableRecords([$unlinked->id, $linked->id])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoErrors()
            ->assertNotified(Notification::make()->title($bulkTitle)->body($bulkBody)->danger())
            ->assertNotNotified(__('filament-actions::delete.multiple.notifications.deleted.title'));

        $this->assertModelExists($unlinked);
        $this->assertModelExists($linked);
        $this->assertModelExists($league);
        $this->assertModelExists($unlinkedMedia);
        $this->assertModelExists($linkedMedia);
        $disk->assertExists($unlinkedMedia->getPathRelativeToRoot());
        $disk->assertExists($linkedMedia->getPathRelativeToRoot());
    }

    public function test_single_delete_displays_english_refusal_without_success_notification(): void
    {
        $this->signInAdmin();
        app()->setLocale('en');
        $discipline = Discipline::factory()->create();
        $league = League::factory()->for($discipline)->create();

        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoErrors()
            ->assertNotified(Notification::make()->title('Cannot delete discipline')->body('The discipline is used by leagues.')->danger())
            ->assertNotNotified(__('filament-actions::delete.single.notifications.deleted.title'));

        $this->assertModelExists($discipline);
        $this->assertModelExists($league);
    }

    public function test_user_with_edit_permission_cannot_invoke_single_deletion_without_delete_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        foreach (['ViewAny', 'Update'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission.':Discipline', 'web'));
        }
        $this->actingAs($user);
        $discipline = Discipline::factory()->create();

        Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()])
            ->assertOk()
            ->assertActionHidden('delete')
            ->mountAction('delete')
            ->assertActionNotMounted();

        $this->assertModelExists($discipline);
    }

    public function test_bulk_delete_requires_individual_delete_permission_before_removing_any_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        foreach (['ViewAny', 'DeleteAny'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission.':Discipline', 'web'));
        }
        $this->actingAs($user);
        $disciplines = Discipline::factory()->count(2)->create();

        Livewire::test(ListDisciplines::class)
            ->selectTableRecords($disciplines->modelKeys())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertForbidden();

        foreach ($disciplines as $discipline) {
            $this->assertModelExists($discipline);
        }
    }

    public function test_bulk_delete_removes_unlinked_media_and_reports_success(): void
    {
        $disk = Storage::fake('public');
        $this->signInAdmin();
        $discipline = Discipline::factory()->create();
        $media = $discipline->addMedia(UploadedFile::fake()->image('icon.png'))->toMediaCollection('icon', 'public');
        $path = $media->getPathRelativeToRoot();

        Livewire::test(ListDisciplines::class)
            ->selectTableRecords([$discipline->id])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoErrors()
            ->assertNotified(__('filament-actions::delete.multiple.notifications.deleted.title'));

        $this->assertModelMissing($discipline);
        $this->assertModelMissing($media);
        $disk->assertMissing($path);
    }

    /** @return array<string, array{bool}> */
    public static function deletionPaths(): array
    {
        return ['single' => [false], 'bulk' => [true]];
    }

    #[DataProvider('deletionPaths')]
    public function test_unrelated_database_failure_is_not_replaced_with_league_refusal(bool $bulk): void
    {
        $this->signInAdmin();
        $discipline = Discipline::factory()->create();
        $page = $bulk
            ? Livewire::test(ListDisciplines::class)->selectTableRecords([$discipline->id])
            : Livewire::test(EditDiscipline::class, ['record' => $discipline->getRouteKey()]);
        $failure = new QueryException('sqlite', 'delete from disciplines', [], new PDOException('Unrelated database failure'));
        DB::connection()->beforeExecuting(function (string $query) use ($failure): void {
            if (str_starts_with($query, 'delete from "disciplines"')) {
                throw $failure;
            }
        });

        try {
            $page->callAction($bulk ? TestAction::make('delete')->table()->bulk() : 'delete');
            $this->fail('The database failure must propagate.');
        } catch (QueryException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertModelExists($discipline);
        Notification::assertNotNotified();
    }
}
