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

        $query = User::query();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $users = $query->orderBy('id')->paginate(10)
            ->appends(['search' => $search]);

        if ($users->currentPage() > $users->lastPage()) {
            return redirect()->route('admin.users.index', [
                'page' => $users->lastPage(),
                'search' => $search !== '' ? $search : null,
            ]);
        }

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }
}
