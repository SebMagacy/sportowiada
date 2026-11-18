<?php

namespace Tests\Feature;

use App\Filament\Resources\Disciplines\DisciplineResource;
use App\Filament\Resources\Disciplines\Pages\CreateDiscipline;
use App\Filament\Resources\Disciplines\Pages\EditDiscipline;
use App\Filament\Resources\Disciplines\Pages\ListDisciplines;
use App\Models\Discipline;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->assertSeeText('Nie utworzono jeszcze żadnej dyscypliny.')
            ->assertSeeText(
                'Dyscyplina określa rodzaj sportu, do którego należą ligi. Dodaj pierwszą dyscyplinę, aby rozpocząć tworzenie katalogu sportowego.',
            );
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
        $description = "Zażółć gęślą jaźń.\nDrugi wiersz.";

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
}
