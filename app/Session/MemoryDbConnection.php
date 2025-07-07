<?php

namespace App\Session;

use App\Trace\TraceCode;
use App\Metrics\Constants as MetricsConstants;
use App\Session\SessionConstants;

/**
 * MemoryDbConnection - Simplified Memory DB connection wrapper with observability
 *
 * This class provides a unified interface for Memory DB operations with comprehensive
 * observability including logging, metrics, and error handling. It uses simple
 * method calls instead of complex command interfaces.
 *
 * Key Features:
 * - Fresh Predis connections from Laravel for each operation
 * - Comprehensive logging with execution time tracking
 * - Prometheus metrics with controlled cardinality
 * - Simple method interface for common Redis operations
 * - Graceful error handling with detailed error reporting
 *
 * Usage Examples:
 * $memoryDb = new MemoryDbConnection();
 * $memoryDb->get('key', 'CACHE_READ');
 * $memoryDb->hset('hash', 'field', 'value', 'HASH_WRITE');
 * $memoryDb->sadd('set', 'member', 'SET_OPERATIONS');
 */
class MemoryDbConnection
{
    /**
     * Get value from Memory DB
     *
     * @param string $key Redis key
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function get(string $key, string $context = SessionConstants::CONTEXT_REDIS_GENERIC_OPERATIONS)
    {
        return $this->executeWithObservability('GET', $key, null, $context);
    }

    /**
     * Set value in Memory DB
     *
     * @param string $key Redis key
     * @param mixed $value Value to set
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function set(string $key, $value, string $context = SessionConstants::CONTEXT_REDIS_GENERIC_OPERATIONS)
    {
        return $this->executeWithObservability('SET', $key, $value, $context);
    }

    /**
     * Delete key(s) from Memory DB
     *
     * @param string|array $keys Key(s) to delete
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function del($keys, string $context = SessionConstants::CONTEXT_REDIS_GENERIC_OPERATIONS)
    {
        return $this->executeWithObservability('DEL', $keys, null, $context);
    }

    /**
     * Get all hash fields and values
     *
     * @param string $key Hash key
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function hgetall(string $key, string $context = SessionConstants::CONTEXT_REDIS_HASH_OPERATIONS)
    {
        return $this->executeWithObservability('HGETALL', $key, null, $context);
    }

    /**
     * Set multiple hash fields
     *
     * @param string $key Hash key
     * @param array $hash Field-value pairs
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function hmset(string $key, array $hash, string $context = SessionConstants::CONTEXT_REDIS_HASH_OPERATIONS)
    {
        return $this->executeWithObservability('HMSET', $key, $hash, $context);
    }

    /**
     * Add member(s) to set
     *
     * @param string $key Set key
     * @param string|array $members Member(s) to add
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function sadd(string $key, $members, string $context = SessionConstants::CONTEXT_REDIS_SET_OPERATIONS)
    {
        return $this->executeWithObservability('SADD', $key, $members, $context);
    }

    /**
     * Remove member(s) from set
     *
     * @param string $key Set key
     * @param string|array $members Member(s) to remove
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function srem(string $key, $members, string $context = SessionConstants::CONTEXT_REDIS_SET_OPERATIONS)
    {
        return $this->executeWithObservability('SREM', $key, $members, $context);
    }

    /**
     * Get all set members
     *
     * @param string $key Set key
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function smembers(string $key, string $context = SessionConstants::CONTEXT_REDIS_SET_OPERATIONS)
    {
        return $this->executeWithObservability('SMEMBERS', $key, null, $context);
    }

    /**
     * Get set cardinality (number of members)
     *
     * @param string $key Set key
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function scard(string $key, string $context = SessionConstants::CONTEXT_REDIS_SET_OPERATIONS)
    {
        return $this->executeWithObservability('SCARD', $key, null, $context);
    }

    /**
     * Set key expiration
     *
     * @param string $key Key to expire
     * @param int $seconds Expiration time in seconds
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function expire(string $key, int $seconds, string $context = SessionConstants::CONTEXT_REDIS_GENERIC_OPERATIONS)
    {
        return $this->executeWithObservability('EXPIRE', $key, $seconds, $context);
    }

    /**
     * Check if key exists
     *
     * @param string|array $keys Key(s) to check
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis result
     */
    public function exists($keys, string $context = SessionConstants::CONTEXT_REDIS_GENERIC_OPERATIONS)
    {
        return $this->executeWithObservability('EXISTS', $keys, null, $context);
    }

    /**
     * Execute Memory DB operation with comprehensive observability
     *
     * @param string $operation Redis operation name
     * @param mixed $key Redis key(s)
     * @param mixed $value Operation value/data
     * @param string $context Operation context for logging and metrics
     * @return mixed Redis operation result
     */
    private function executeWithObservability(string $operation, $key, $value, string $context)
    {
        $startTime = microtime(true);
        $connection = null;
        $result = null;
        $success = false;

        try {
            // Get fresh Predis connection from Laravel
            $connection = $this->getConnection();

            // Execute the operation using simple method calls
            $result = $this->executeOperation($connection, $operation, $key, $value);

            $success = true;
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Record metrics for successful operation
            $this->recordMetrics($operation, $context, $duration, true, $result);

            return $result;

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Log failed operation
            app('trace')->error(TraceCode::MEMORY_DB_OPERATION_FAILED, [
                'operation' => $operation,
                'context' => $context,
                'duration_ms' => round($duration, 2),
                'key' => is_array($key) ? 'array[' . count($key) . ']' : $key,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Record metrics for failed operation
            $this->recordMetrics($operation, $context, $duration, false);

            // Re-throw the exception to maintain original behavior
            throw $e;
        }
    }

    /**
     * Execute specific Redis operation using simple method calls
     *
     * @param \Predis\Client $connection Redis connection
     * @param string $operation Operation name
     * @param mixed $key Key(s)
     * @param mixed $value Value/data
     * @return mixed Operation result
     */
    private function executeOperation($connection, string $operation, $key, $value)
    {
        switch (strtoupper($operation)) {
            case 'GET':
                return $connection->get($key);

            case 'SET':
                return $connection->set($key, $value);

            case 'DEL':
                return is_array($key) ? $connection->del($key) : $connection->del([$key]);

            case 'HGETALL':
                return $connection->hgetall($key);

            case 'HMSET':
                return $connection->hmset($key, $value);

            case 'SADD':
                return is_array($value) ? $connection->sadd($key, ...$value) : $connection->sadd($key, $value);

            case 'SREM':
                return is_array($value) ? $connection->srem($key, ...$value) : $connection->srem($key, $value);

            case 'SMEMBERS':
                return $connection->smembers($key);

            case 'SCARD':
                return $connection->scard($key);

            case 'EXISTS':
                return is_array($key) ? $connection->exists(...$key) : $connection->exists($key);

            default:
                throw new \InvalidArgumentException("Unsupported operation: {$operation}");
        }
    }

    /**
     * Get fresh Redis connection from Laravel's cache manager
     *
     * @return \Predis\Client
     * @throws \Exception if connection fails
     */
    private function getConnection()
    {
        try {
            return app('cache')->store('session_redis')->connection()->client();
        } catch (\Exception $e) {
            app('trace')->error(TraceCode::MEMORY_DB_CONNECTION_FAILED, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'store' => 'session_redis',
                'context' => 'MemoryDbConnection'
            ]);
            throw $e;
        }
    }

    /**
     * Record Prometheus metrics for Memory DB operations
     *
     * @param string $operation Redis operation
     * @param string $context Operation context
     * @param float $duration Operation duration in milliseconds
     * @param bool $success Whether operation was successful
     * @param mixed $result Operation result for key status detection
     */
    private function recordMetrics(string $operation, string $context, float $duration, bool $success, $result = null): void
    {
        try {
            $dimensions = [
                'operation' => strtoupper($operation),
                'context' => $context,
                'status' => $success ? MetricsConstants::MEMORY_DB_STATUS_SUCCESS : MetricsConstants::MEMORY_DB_STATUS_FAILURE,
                'key_status' => $success ? $this->determineKeyStatus($operation, $result) : MetricsConstants::MEMORY_DB_KEY_UNKNOWN
            ];

            // Count total operations
            app('metrics')->count(
                MetricsConstants::METRIC_COUNTER_MEMORY_DB_OPERATIONS,
                MetricsConstants::EVENT_COUNT_ONE,
                $dimensions
            );

            // Record operation duration
            app('metrics')->histogram(
                MetricsConstants::METRIC_HISTOGRAM_MEMORY_DB_OPERATION_DURATION,
                $duration,
                $dimensions
            );

        } catch (\Throwable $t) {
            app('trace')->warning(TraceCode::MEMORY_DB_METRICS_PUSH_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
                'context' => 'MemoryDbConnection metrics recording'
            ]);
        }
    }

    /**
     * Determine key status based on operation type and result for primary storage
     *
     * @param string $operation Redis operation name
     * @param mixed $result Operation result
     * @return string Key status constant
     */
    private function determineKeyStatus(string $operation, $result): string
    {
        switch (strtoupper($operation)) {
            case 'GET':
                // null = key not found, anything else = key found
                return ($result === null) ? MetricsConstants::MEMORY_DB_KEY_NOT_FOUND : MetricsConstants::MEMORY_DB_KEY_FOUND;

            case 'HGETALL':
            case 'SMEMBERS':
                // Empty array = key not found (hash/set doesn't exist)
                // Non-empty array = key found
                return (is_array($result) && count($result) === 0) ? MetricsConstants::MEMORY_DB_KEY_NOT_FOUND : MetricsConstants::MEMORY_DB_KEY_FOUND;

            case 'EXISTS':
                // 0 = key not found, > 0 = key found
                return ($result === 0) ? MetricsConstants::MEMORY_DB_KEY_NOT_FOUND : MetricsConstants::MEMORY_DB_KEY_FOUND;

            case 'SCARD':
                // 0 = set doesn't exist or empty, > 0 = set exists with members
                return ($result === 0) ? MetricsConstants::MEMORY_DB_KEY_NOT_FOUND : MetricsConstants::MEMORY_DB_KEY_FOUND;

            case 'SET':
            case 'HMSET':
                return MetricsConstants::MEMORY_DB_KEY_WRITTEN;

            case 'SADD':
                // Returns number of elements added
                // Could be 0 if elements already existed, but key was accessed/modified
                return MetricsConstants::MEMORY_DB_KEY_MODIFIED;

            case 'DEL':
            case 'SREM':
                // Returns number of keys/elements removed
                // 0 = nothing was deleted (key/element didn't exist)
                // > 0 = something was deleted
                return ($result > 0) ? MetricsConstants::MEMORY_DB_KEY_DELETED : MetricsConstants::MEMORY_DB_KEY_NOT_FOUND;

            case 'EXPIRE':
                // 1 = key existed and expiration was set
                // 0 = key didn't exist
                return ($result === 1) ? MetricsConstants::MEMORY_DB_KEY_MODIFIED : MetricsConstants::MEMORY_DB_KEY_NOT_FOUND;

            default:
                return MetricsConstants::MEMORY_DB_KEY_UNKNOWN;
        }
    }
}
