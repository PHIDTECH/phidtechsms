<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        [$key, $secret] = $this->extractCredentials($request);

        if (!$key || !$secret) {
            return response()->json([
                'success' => false,
                'error' => 'Missing API credentials. Provide X-API-KEY and X-API-SECRET headers or Basic auth.'
            ], 401);
        }

        $record = ApiKey::with('user')->where('key', $key)->where('active', true)->first();
        if (!$record) {
            return response()->json(['success' => false, 'error' => 'Invalid API key'], 401);
        }

        $computed = hash_hmac('sha256', $secret, config('app.key'));
        if (!hash_equals($record->secret_hash, $computed)) {
            return response()->json(['success' => false, 'error' => 'Invalid API secret'], 401);
        }

        // Enforce IP allowlist if configured
        $ip = $request->ip();
        $allow = $record->ip_allowlist ?? [];
        if (is_array($allow) && count($allow) > 0) {
            if (!in_array($ip, $allow, true)) {
                return response()->json(['success' => false, 'error' => 'IP not allowed'], 403);
            }
        }

        // Per-key rate limiting (per minute)
        $limit = $record->rate_limit_per_min ?? 60; // default 60/min if not set
        if ($limit > 0) {
            $bucket = 'api_rate:'.$record->id.':'.now()->format('YmdHi');
            $count = Cache::increment($bucket);
            Cache::put($bucket, $count, now()->addMinutes(2));
            if ($count > $limit) {
                return response()->json(['success' => false, 'error' => 'Rate limit exceeded'], 429);
            }
        }

        // Attach user to the request context (stateless)
        if ($record->user) {
            Auth::setUser($record->user);
        }

        // Update usage timestamp (no need to block if it fails later)
        $record->forceFill(['last_used_at' => now()])->save();

        // Expose API key to downstream if needed
        $request->attributes->set('api_key_record', $record);

        return $next($request);
    }

    private function extractCredentials(Request $request): array
    {
        $key = $request->header('X-API-KEY');
        $secret = $request->header('X-API-SECRET');

        if ($key && $secret) {
            return [$key, $secret];
        }

        // Support Basic auth: base64(key:secret)
        $auth = $request->header('Authorization');
        if ($auth && str_starts_with($auth, 'Basic ')) {
            $decoded = base64_decode(substr($auth, 6));
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$k, $s] = explode(':', $decoded, 2);
                return [trim($k), $s];
            }
        }

        return [null, null];
    }
}
