<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Input;
use Modules\Dashboard\Entities\DashboardServiceHelper;
class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request)
    {
        $pageTitle = 'Fulfillment Center';
        $pageSubTitle = 'Dashboard';

        return view('dashboard::index', compact(
            'pageTitle',
            'pageSubTitle'
        ));
    }

}
