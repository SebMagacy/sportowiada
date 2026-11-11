<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $search = $request->string('search')->trim()->toString();

        $accountType = $request->query('account_type', 'all');

        if (! in_array($accountType, ['all', 'admin', 'user'], true)) {
            $accountType = 'all';
        }

        $perPage = $request->query('per_page', '10');

        if (! in_array($perPage, ['10', '25', '50'], true)) {
            $perPage = '10';
        }

        $activeFiltersCount = 0;

        if ($accountType !== 'all') {
            $activeFiltersCount++;
        }

        $columns = [
            'name' => [
                'label' => __('users.name'),
                'sortable' => true,
                'align' => 'left',
            ],
            'email' => [
                'label' => __('users.email'),
                'sortable' => true,
                'align' => 'left',
            ],
            'is_admin' => [
                'label' => __('users.account_type'),
                'sortable' => false,
                'align' => 'left',
            ],
            'id' => [
                'label' => 'ID',
                'sortable' => true,
                'align' => 'right',
            ],
            'actions' => [
                'label' => __('users.actions'),
                'sortable' => false,
                'align' => 'right',
            ],
        ];

        $sortableColumns = array_keys(array_filter(
            $columns,
            fn (array $column): bool => $column['sortable'],
        ));

        $sort = $request->query('sort');
        $direction = $request->query('direction', 'asc');

        if (! in_array($sort, $sortableColumns, true)) {
            $sort = null;
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        if ($sort === null) {
            $direction = null;
        }

        $query = User::query();

        if ($accountType !== 'all') {
            $query->where('is_admin', $accountType === 'admin');
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($sort !== null) {
            $query->orderBy($sort, $direction);
        }

        if ($sort !== 'id') {
            $query->orderBy('id');
        }

        $users = $query->paginate((int) $perPage)
            ->appends([
                'search' => $search,
                'account_type' => $accountType,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ]);

        if ($users->currentPage() > $users->lastPage()) {
            Session::keep('status');

            return redirect()->route('admin.users.index', [
                'page' => $users->lastPage(),
                'search' => $search !== '' ? $search : null,
                'account_type' => $accountType,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ]);
        }

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'accountType' => $accountType,
            'perPage' => $perPage,
            'activeFiltersCount' => $activeFiltersCount,
            'sort' => $sort,
            'direction' => $direction,
            'columns' => $columns,
            'clearSearchUrl' => route('admin.users.index', [
                'account_type' => $accountType,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,

            ]),
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'listParameters' => $this->listParameters($request),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'is_admin' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && ! $request->boolean('is_admin')) {
            throw ValidationException::withMessages([
                'is_admin' => __('users.cannot_demote_self'),
            ]);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $request->boolean('is_admin');
        $user->save();

        return redirect()->route('admin.users.index', $this->listParameters($request))
            ->with('status', __('users.updated'));
    }

    /**
     * @return array<string, string>
     */
    private function listParameters(Request $request): array
    {
        return array_filter(
            $request->only([
                'search',
                'account_type',
                'sort',
                'direction',
                'per_page',
                'page',
            ]),
            fn (mixed $value): bool => is_string($value),
        );
    }
}
