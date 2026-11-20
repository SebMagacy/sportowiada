<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class DisciplineHasLeaguesException extends Exception implements ShouldntReport
{
    //
}
