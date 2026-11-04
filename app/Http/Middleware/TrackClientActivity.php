<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;

class TrackClientActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Filter out background polling, API checks, downloads, and asset requests
        $path = $request->path();
        if (
            $request->is('api/*') ||
            $request->is('storage/*') ||
            $request->is('css/*') ||
            $request->is('js/*') ||
            $request->is('up') ||
            $request->is('*/download') ||
            $request->is('*/*/download') ||
            $path === 'favicon.ico' ||
            $request->ajax()
        ) {
            return $response;
        }

        // Only log GET requests to main page views to avoid duplicate logs on POST actions
        if ($request->isMethod('GET')) {
            $clientIp = $request->ip() ?: '127.0.0.1';
            $sessionKey = 'tracked_client_' . md5($clientIp . '_' . $path);

            // Throttle duplicate page hit logs for the same IP + Path to once every 3 minutes
            if (!Cache::has($sessionKey)) {
                Cache::put($sessionKey, true, now()->addMinutes(3));

                $isLocalhost = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
                $isLanClient = !$isLocalhost;

                $action = $isLanClient ? 'Client Connected' : 'Workstation Navigation';
                $details = $isLanClient ? "LAN client accessed /{$path}" : "Master host accessed /{$path}";

                ActivityLogger::log($action, $details, 'info', $request);
            }
        }

        return $response;
    }
}

