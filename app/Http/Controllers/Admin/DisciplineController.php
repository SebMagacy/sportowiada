<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function create(): View
    {
        return view('admin.disciplines.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'alias' => [
                'bail',
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('disciplines', 'alias'),
            ],
        ]);

        $discipline = new Discipline;
        $discipline->name = $validated['name'];
        $discipline->alias = $validated['alias'];
        $discipline->save();

        return redirect()->route('admin.disciplines.index')
            ->with('status', __('disciplines.created'));
    }
}
