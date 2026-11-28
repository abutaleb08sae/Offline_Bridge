<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotTabBlocked
{
    /**
     * Block student access to lab tasks, submissions, and judge routes when flagged for tab violation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isAdmin() && $user->isTabBlocked()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'blocked' => true,
                    'message' => 'Access Blocked due to Browser Tab Policy Violation',
                ], 403);
            }

            $latestViolation = $user->tabViolations()
                ->where('is_blocked', true)
                ->latest()
                ->first();

            return response()->view('judge.tab_blocked', [
                'latestViolation' => $latestViolation,
            ], 403);
        }

        return $next($request);
    }
}
