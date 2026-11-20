<?php

namespace App\Actions;

use App\Exceptions\DisciplineHasLeaguesException;
use App\Models\Discipline;
use App\Models\League;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class DeleteDisciplines
{
    /**
     * @param  list<int>  $disciplineIds
     */
    public function handle(array $disciplineIds): int
    {
        return DB::transaction(function () use ($disciplineIds): int {
            $disciplines = Discipline::query()
                ->whereKey($disciplineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($disciplines as $discipline) {
                Gate::authorize('delete', $discipline);
            }

            $linkedLeague = League::query()
                ->whereIn('discipline_id', $disciplines->modelKeys())
                ->lockForUpdate()
                ->first(['id']);

            if ($linkedLeague !== null) {
                throw new DisciplineHasLeaguesException;
            }

            foreach ($disciplines as $discipline) {
                if (! $discipline->delete()) {
                    throw new RuntimeException('Discipline deletion failed.');
                }
            }

            return $disciplines->count();
        });
    }
}
