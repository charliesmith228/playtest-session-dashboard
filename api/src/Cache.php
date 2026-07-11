<?php

declare(strict_types=1);

namespace App;

use Redis;
use RedisException;

class Cache
{
    private Redis $redis;

    public function __construct(string $host, int $port)
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect($host, $port);
        } catch (RedisException $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            // If Redis is unavailable, continue without caching
        }
    }

    // Retrieve a cached value by key
    // Returns the decoded value, or null if the key doesn't exist
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        // Values are stored as JSON strings, so decode on the way out
        return json_decode($value, true);
    }

    // Store a value under a key with an optional time to live in seconds
    // TTL defaults to 1 hour - after that Redis automatically deletes the key
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        // Encode as JSON so we can store arrays and objects, not just strings
        $this->redis->setex($key, $ttl, json_encode($value));
    }

    // Delete a specific cached value
    public function forget(string $key): void
    {
        $this->redis->del($key);
    }

    // Delete all keys matching a pattern
    public function forgetPattern(string $pattern): void
    {
        $keys = $this->redis->keys($pattern);

        if (!empty($keys)) {
            $this->redis->del($keys);
        }
    }
}
