<?php

namespace Modules\Cashier\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CashierController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return redirect()->route('cashier.dashboard');
    }

    public function dashboard(Request $request)
    {
        return redirect()->route('sales.pos');
    }

}
