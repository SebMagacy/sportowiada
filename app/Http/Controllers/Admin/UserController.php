<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $users = $query->paginate(10)
            ->appends([
                'search' => $search,
                'account_type' => $accountType,
                'sort' => $sort,
                'direction' => $direction,
            ]);

        if ($users->currentPage() > $users->lastPage()) {
            return redirect()->route('admin.users.index', [
                'page' => $users->lastPage(),
                'search' => $search !== '' ? $search : null,
                'account_type' => $accountType,
                'sort' => $sort,
                'direction' => $direction,
            ]);
        }

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'accountType' => $accountType,
            'sort' => $sort,
            'direction' => $direction,
            'columns' => $columns,
        ]);
    }
}
