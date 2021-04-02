<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const Strategies = [
        'ringall' => 'Ring all in list',
        'hunt' => 'Ring one at a time',
        'firstavailable' => 'Ring first available user',
        'random' => 'Randomly selects users untill answer'
    ];

}
