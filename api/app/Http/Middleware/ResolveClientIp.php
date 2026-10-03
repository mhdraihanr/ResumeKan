<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pakai X-Real-IP Railway sebagai REMOTE_ADDR agar `$request->ip()` stabil;
 * tanpa ini edge memberi CGNAT berbeda tiap request dan kunci throttle pecah.
 */
class ResolveClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $realIp = $request->headers->get('X-Real-IP');

        if ($this->isPublicIp($realIp)) {
            $request->server->set('REMOTE_ADDR', $realIp);
            $request->headers->remove('X-Forwarded-For');
            $request->headers->remove('Forwarded');
        }

        return $next($request);
    }

    /** Hanya IP publik; nilai privat/CGNAT ditolak agar X-Real-IP tidak bisa dipalsukan. */
    private function isPublicIp(?string $ip): bool
    {
        return $ip !== null
            && filter_var($ip, FILTER_VALIDATE_IP) !== false
            && ! IpUtils::checkIp($ip, IpUtils::PRIVATE_SUBNETS);
    }
}
