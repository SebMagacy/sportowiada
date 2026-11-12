<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use Illuminate\View\View;

class DisciplineController extends Controller
{
    public function index(): View
    {
        $disciplines = Discipline::query()
            ->orderBy('id')
            ->get();

        $columns = [
            'name' => [
                'label' => __('disciplines.name'),
                'sortable' => false,
                'align' => 'left',
            ],
        ];

        return view('admin.disciplines.index', [
            'disciplines' => $disciplines,
            'columns' => $columns,
        ]);
    }
}
