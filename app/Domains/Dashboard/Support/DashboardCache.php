<?php

namespace App\Domains\Dashboard\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    private const TAG = 'dashboard.metrics';
    private const TTL = 900; // seconds

    /**
     * @return array<string, mixed>
     */
    public static function remember(User $user, callable $resolver, ?int $ttl = null): array
    {
        $key = self::keyForUser($user);
        
        // Array driver doesn't support tags, use simple cache
        if (self::supportsTagging()) {
            return Cache::tags(self::TAG)->remember($key, $ttl ?? self::TTL, fn () => $resolver());
        }
        
        return Cache::remember($key, $ttl ?? self::TTL, fn () => $resolver());
    }

    public static function flush(?User $user = null): void
    {
        if ($user) {
            $key = self::keyForUser($user);
            
            if (self::supportsTagging()) {
                Cache::tags(self::TAG)->forget($key);
            } else {
                Cache::forget($key);
            }

            return;
        }

        if (self::supportsTagging()) {
            Cache::tags(self::TAG)->flush();
        } else {
            // For non-tagging drivers, we need to flush all cache
            Cache::flush();
        }
    }

    private static function supportsTagging(): bool
    {
        $store = Cache::getStore();
        
        // Check if the store supports tagging by checking its class
        return method_exists($store, 'tags');
    }

    private static function keyForUser(User $user): string
    {
        return sprintf('dashboard:metrics:user:%s', $user->getAuthIdentifier());
    }
}
