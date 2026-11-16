<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Restrict access to users with the 'admin' role (NOC / Administrator).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== 'admin') {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Administrator / NOC.');
        }

        return $next($request);
    }
}
