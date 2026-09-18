<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStudent()) {
            $student = $user->student;
            
            if (!$student) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
            }

            if ($student->status !== 'active') {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Akun PKL Anda telah dinonaktifkan.');
            }

            $endDate = \Carbon\Carbon::parse($student->end_date)->endOfDay();
            if (now()->greaterThan($endDate)) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Masa PKL Anda telah berakhir pada ' . $endDate->format('d M Y') . '. Hubungi Admin untuk perpanjangan.');
            }
        }

        return $next($request);
    }
}
