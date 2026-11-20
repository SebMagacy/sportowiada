<?php

namespace Tests\Feature;

use App\Actions\DeleteDisciplines;
use App\Exceptions\DisciplineHasLeaguesException;
use App\Models\Discipline;
use App\Models\League;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteDisciplinesConcurrencyTest extends TestCase
{
    public function test_mysql_blocks_concurrent_league_assignment_during_deletion(): void
    {
        $this->prepareMysqlDatabase();
        $this->actingAs($this->createPanelAdministrator());
        $discipline = Discipline::factory()->create();
        $attemptedAssignment = false;

        DB::listen(function (QueryExecuted $query) use ($discipline, &$attemptedAssignment): void {
            if ($attemptedAssignment || $query->connectionName !== 'mysql'
                || ! str_contains($query->sql, 'from `disciplines`')
                || ! str_contains($query->sql, 'for update')) {
                return;
            }

            $attemptedAssignment = true;

            try {
                DB::connection('concurrent')->table('leagues')->insert([
                    'discipline_id' => $discipline->id,
                    'name' => 'Concurrent league',
                ]);
                $this->fail('League assignment must wait for the deletion lock.');
            } catch (QueryException $exception) {
                $this->assertSame(1205, $exception->errorInfo[1]);
            }
        });

        $deletedCount = (new DeleteDisciplines)->handle([$discipline->id]);

        $this->assertTrue($attemptedAssignment);
        $this->assertSame(1, $deletedCount);
        $this->assertModelMissing($discipline);
        $this->assertDatabaseCount('leagues', 0);

        try {
            DB::connection('concurrent')->table('leagues')->insert([
                'discipline_id' => $discipline->id,
                'name' => 'Assignment after deletion',
            ]);
            $this->fail('The foreign key must reject assignment after deletion.');
        } catch (QueryException $exception) {
            $this->assertSame(1452, $exception->errorInfo[1]);
        }
    }

    public function test_mysql_detects_league_committed_after_transaction_snapshot_and_preserves_media(): void
    {
        $this->prepareMysqlDatabase();
        $disk = Storage::fake('public');
        $this->actingAs($this->createPanelAdministrator());
        $discipline = Discipline::factory()->create();
        $media = $discipline->addMedia(UploadedFile::fake()->image('icon.png'))->toMediaCollection('icon', 'public');
        $assigned = false;

        DB::connection()->beforeExecuting(function (string $query) use ($discipline, &$assigned): void {
            if ($assigned || ! str_contains($query, 'from `disciplines`') || ! str_contains($query, 'for update')) {
                return;
            }

            $assigned = true;
            DB::connection('concurrent')->table('leagues')->insert([
                'discipline_id' => $discipline->id,
                'name' => 'Committed after snapshot',
            ]);
        });

        try {
            DB::transaction(function () use ($discipline): void {
                League::query()->count();
                (new DeleteDisciplines)->handle([$discipline->id]);
            });
            $this->fail('Deletion must detect the concurrently committed league.');
        } catch (DisciplineHasLeaguesException) {
            $this->assertTrue($assigned);
        }

        $this->assertModelExists($discipline);
        $this->assertModelExists($media);
        $this->assertDatabaseHas('leagues', ['discipline_id' => $discipline->id, 'name' => 'Committed after snapshot']);
        $disk->assertExists($media->getPathRelativeToRoot());
    }

    private function prepareMysqlDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql'
            || ! preg_match('/^sportowiada_test_[a-f0-9]+$/', DB::connection()->getDatabaseName())) {
            $this->markTestSkipped('Requires an isolated MySQL database named sportowiada_test_<hex>.');
        }

        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        DB::connection()->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        config(['database.connections.concurrent' => config('database.connections.mysql')]);
        DB::connection('concurrent')->statement('SET SESSION innodb_lock_wait_timeout = 1');
    }
}
