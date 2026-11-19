<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as FilamentDashboard;
use Illuminate\Contracts\View\View as BladeView;

class Dashboard extends FilamentDashboard
{
    public function getHeader(): BladeView
    {
        return view('filament.catalog-header');
    }
}
