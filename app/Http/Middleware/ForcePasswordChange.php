<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user && $user->must_change_password) {
            // Allow access to the change-password path and logout only
            $allowed = [
                route('password.change.edit', absolute: false),
                route('password.change.update', absolute: false),
                route('logout', absolute: false),
            ];

            $currentPath = $request->path();
            // If request is not to allowed paths, redirect
            if (!in_array('/' . trim($currentPath, '/'), array_map(function ($r) { return '/' . trim(parse_url($r, PHP_URL_PATH), '/'); }, $allowed))) {
                return redirect()->route('password.change.edit')->with('warning', 'Please change your password to continue.');
            }
        }

        return $next($request);
    }
}