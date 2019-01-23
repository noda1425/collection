<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * []
     * @return
     */
    public function converter()
    {
        return view('tools.jsonToArrayConverter');
    }

    /**
     * []
     * @return
     */
    public function jsonToArrayConvert()
    {
        dd(1);
        return view('tools.jsonToArrayConverter');
    }
}
