<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role ?: 'admin';
        abort_unless(in_array($role, $roles, true), 403, 'You do not have permission to access this module.');

        return $next($request);
    }
}
