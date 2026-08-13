<?php

namespace App\Core\Modules\Member\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberOnly
{
    /**
     * Hanya member (bukan superadmin) yang boleh akses area /member.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
