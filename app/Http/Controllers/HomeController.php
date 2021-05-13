<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    /**
     * get current user.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function user()
    {
        return collect([
            'status' => 'success',
            'message' => '',
            'data' => Auth::user()->only('id', 'name')
        ]);
    }
}
