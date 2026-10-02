<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the request only for approved users holding one of the given roles.
 * Admins are always allowed. Usage: ->middleware('role:doctor,lab_tech').
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->deny($request, 401, 'يجب تسجيل الدخول أولاً');
        }
        if (!$user->isApproved()) {
            return $this->deny($request, 403, 'حسابك بانتظار موافقة الأدمن');
        }
        if (!$user->isAdmin() && !in_array($user->role, $roles, true)) {
            return $this->deny($request, 403, 'ليس لديك صلاحية لهذا الإجراء');
        }

        return $next($request);
    }

    private function deny(Request $request, int $status, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['status' => 'error', 'message' => $message], $status);
        }
        abort($status, $message);
    }
}
