<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $users = User::query()->orderBy('id')->paginate(10);

        if ($users->currentPage() > $users->lastPage()) {
            return redirect()->route('admin.users.index', [
                'page' => $users->lastPage(),
            ]);
        }

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }
}
