<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if ($user && $user->isStudent()) {
            return view('dashboard.student');
        }

        return view('dashboard.admin');
    }
}
