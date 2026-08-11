<?php

namespace App\Core\Modules\Admin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    /**
     * Hanya user superadmin yang boleh lewat (FR-006, permission mapping).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Akses khusus superadmin.');
        }

        return $next($request);
    }
}
