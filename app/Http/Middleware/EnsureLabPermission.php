<?php

namespace App\Http\Middleware;

use App\Support\LabAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLabPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(LabAccess::allows($request->user(), $permission), 403);

        return $next($request);
    }
}
