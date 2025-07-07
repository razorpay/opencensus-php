<?php

namespace App\Session;

use Carbon\Carbon;
use App\Trace\TraceCode;

/**
 * SessionUtils - Centralized session utility functions
 *
 * This utility class provides a single source of truth for session operations,
 * ensuring consistency across different parts of the application.
 *
 * Used by:
 * - app/Session/CustomCacheBasedSessionHandler.php::destroy()
 * - app/Session/Entity.php (session deletion methods)
 * - app/Cron/Jobs/SessionCleanup/SessionCleanupCronJob.php::cleanupExpiredSession() (will be added in next PR)
 *
 * Design benefits:
 * - Eliminates code duplication between session handler and cron job (will be added in next PR)
 * - Provides consistent Redis key generation across the application
 * - Centralizes session utility functions for easier maintenance
 * - Ensures both session destruction and cron cleanup work identically
 * - Supports dual write operations for Redis to Memory DB migration
 * - Provides comprehensive observability for all Redis operations
 */
class SessionUtils
{
    /**
     * Session namespace used for Redis keys
     */
    public const SESSION_NAMESPACE = 'sessions';

    /**
     * Session timeout key used in session payload
     */
    public const SESSION_TIMEOUT_KEY = 'session_timeout';

    /**
     * Clean up a session from Memory DB with comprehensive logging and observability
     *
     * This method handles Memory DB cleanup with detailed tracking:
     * 1. Deletes the main session hash from Memory DB
     * 2. Removes session ID from user sessions set (if user_id exists)
     * 3. Removes session ID from admin sessions set (if admin_id exists)
     * 4. Cleans up empty sets to prevent memory leaks
     * 5. Logs all operations with detailed success/failure tracking
     * 6. Provides comprehensive observability through MemoryDbConnection
     *
     * @param string $sessionId Session ID to clean up
     * @param array $sessionData Session data containing user_id and admin_id
     * @param string $context Context for logging (e.g., 'Session Handler', 'Session Entity')
     */
    public static function cleanupSessionFromMemoryDb(string $sessionId, array $sessionData, string $context = SessionConstants::DEFAULT_CONTEXT): bool
    {
        $memoryDb = new MemoryDbConnection();

        try {
            $sessionKey = self::getSessionKey($sessionId);

            // Delete the main session data and track if it existed
            $mainSessionDeleted = $memoryDb->del($sessionKey, $context);

            $userSessionRemoved = 0;
            $adminSessionRemoved = 0;
            $userEmptySetRemoved = 0;
            $adminEmptySetRemoved = 0;

            // Remove from user sessions set and cleanup if empty
            if (!empty($sessionData['user_id'])) {
                $userKey = self::getUserSessionKey($sessionData['user_id']);
                $userSessionRemoved = $memoryDb->srem($userKey, $sessionId, $context);

                // Check and delete empty set to prevent memory leaks
                $setSize = $memoryDb->scard($userKey, $context);
                if ($setSize === 0) {
                    $userEmptySetRemoved = $memoryDb->del($userKey, $context);
                }
            }

            // Remove from admin sessions set and cleanup if empty
            if (!empty($sessionData['admin_id'])) {
                $adminKey = self::getAdminSessionKey($sessionData['admin_id']);
                $adminSessionRemoved = $memoryDb->srem($adminKey, $sessionId, $context);

                // Check and delete empty set to prevent memory leaks
                $setSize = $memoryDb->scard($adminKey, $context);
                if ($setSize === 0) {
                    $adminEmptySetRemoved = $memoryDb->del($adminKey, $context);
                }
            }

            app('trace')->info(TraceCode::SESSION_MEMORY_DB_DESTROY_SUCCESS, [
                'session_id' => $sessionId,
                'admin_id' => $sessionData['admin_id'] ?? null,
                'user_id' => $sessionData['user_id'] ?? null,
                'context' => $context,
                'existed_in_memory_db' => $mainSessionDeleted > 0,
                'user_session_removed' => $userSessionRemoved > 0,
                'admin_session_removed' => $adminSessionRemoved > 0,
                'user_empty_set_removed' => $userEmptySetRemoved > 0,
                'admin_empty_set_removed' => $adminEmptySetRemoved > 0
            ]);

            return true;

        } catch (\Exception $e) {
            app('trace')->error(TraceCode::SESSION_MEMORY_DB_DESTROY_FAILED, [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'admin_id' => $sessionData['admin_id'] ?? null,
                'user_id' => $sessionData['user_id'] ?? null,
                'context' => $context
            ]);
            // TODO: [MIGRATION PHASE 2] Throw exception once Memory DB becomes primary session store
            // Currently logs error but doesn't fail - Redis operations continue
            return false;
        }
    }

    /**
     * Delete session relation from Memory DB with observability (for Entity class usage)
     *
     * This method handles individual session relation cleanup from Memory DB:
     * 1. Removes session ID from the specified set (user or admin)
     * 2. Cleans up empty sets to prevent memory leaks
     * 3. Logs operations with detailed tracking
     * 4. Provides comprehensive observability through MemoryDbConnection
     *
     * @param string $setKey Redis set key (user or admin sessions set)
     * @param string $sessionId Session ID to remove
     * @param string|null $userId User ID (if removing from user set)
     * @param string|null $adminId Admin ID (if removing from admin set)
     * @param string $context Action description for logging
     */
    public static function deleteSessionRelationFromMemoryDb(string $setKey, string $sessionId, string $userId = null, string $adminId = null, string $context = SessionConstants::DEFAULT_ACTION): void
    {
        $memoryDb = new MemoryDbConnection();

        try {
            $sessionRemoved = $memoryDb->srem($setKey, $sessionId, $context);
            $emptySetDeleted = 0;

            // Check and delete empty set to prevent memory leaks
            $setSize = $memoryDb->scard($setKey, $context);
            if ($setSize === 0) {
                $emptySetDeleted = $memoryDb->del($setKey, $context);
            }

            app('trace')->info(TraceCode::SESSION_MEMORY_DB_DESTROY_SUCCESS, [
                'session_id' => $sessionId,
                'admin_id' => $adminId,
                'user_id' => $userId,
                'action' => $context,
                'set_existed' => $emptySetDeleted > 0,
                'session_existed' => $sessionRemoved > 0
            ]);
        } catch (\Exception $e) {
            app('trace')->error(TraceCode::SESSION_MEMORY_DB_DESTROY_FAILED, [
                'session_id' => $sessionId,
                'admin_id' => $adminId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'context' => 'Session relation deletion from Memory DB'
            ]);
            // TODO: [MIGRATION PHASE 2] Throw exception once Memory DB becomes primary session store
            // Currently logs error but doesn't fail - Redis operations continue
        }
    }

    public static function getSessionDataFromMemoryDb(string $sessionId): array
    {
        $memoryDb = new MemoryDbConnection();
        try {
            $sessionKey = SessionUtils::getSessionKey($sessionId);
            return $memoryDb->hgetall($sessionKey, SessionConstants::CONTEXT_REDIS_SESSION_READ);
        } catch (\Exception $e) {
            app('trace')->error(TraceCode::SESSION_MEMORY_DB_READ_FAILED, [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'context' => 'SessionUtils::getSessionDataFromMemoryDb'
            ]);
            throw $e;
        }
    }

    /**
     * Write session data to Memory DB with error handling, trace logging and observability
     */
    public static function writeSessionToMemoryDb($sessionId, $data): void
    {
        $memoryDb = new MemoryDbConnection();

        try {
            // Memory DB doesn't support TTL, so we rely on session_timeout in payload
            // Note: session_timeout is already added in getDefaultPayload()

            $sessionKey = SessionUtils::getSessionKey($sessionId);

            // Write main session data to Memory DB
            $memoryDb->hmset($sessionKey, $data, SessionConstants::CONTEXT_REDIS_SESSION_WRITE);

            // Write admin session relationships (without TTL as Memory DB doesn't support it)
            if (isset($data['admin_id'])) {
                $adminKey = SessionUtils::getAdminSessionKey($data['admin_id']);
                $memoryDb->sadd($adminKey, $sessionId, SessionConstants::CONTEXT_REDIS_SET_OPERATIONS);
            }

            // Write user session relationships (without TTL as Memory DB doesn't support it)
            if (isset($data['user_id'])) {
                $userKey = SessionUtils::getUserSessionKey($data['user_id']);
                $memoryDb->sadd($userKey, $sessionId, SessionConstants::CONTEXT_REDIS_SET_OPERATIONS);
            }

            app('trace')->info(TraceCode::SESSION_MEMORY_DB_WRITE_SUCCESS, [
                'session_id' => $sessionId,
                'admin_id' => $data['admin_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'session_timeout' => $data['session_timeout'] ?? null
            ]);

        } catch (\Exception $e) {
            app('trace')->error(TraceCode::SESSION_MEMORY_DB_WRITE_FAILED, [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'admin_id' => $data['admin_id'] ?? null,
                'user_id' => $data['user_id'] ?? null
            ]);
            // TODO: [MIGRATION PHASE 2] Throw exception once Memory DB becomes primary session store
            // Currently logs error but doesn't fail - Redis write continues
        }
    }

    /**
     * Check if session exists in Memory DB with observability
     *
     * @param string $sessionId Session ID to check
     * @return bool True if session exists
     */
    public static function sessionExistsInMemoryDb(string $sessionId): bool
    {
        $memoryDb = new MemoryDbConnection();

        try {
            $sessionKey = self::getSessionKey($sessionId);
            return $memoryDb->exists($sessionKey, SessionConstants::CONTEXT_REDIS_GENERIC_OPERATIONS) > 0;
        } catch (\Exception $e) {
            app('trace')->error(TraceCode::SESSION_MEMORY_DB_DESTROY_FAILED, [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'operation' => 'exists_check',
                'context' => 'SessionUtils::sessionExistsInMemoryDb'
            ]);
            return false;
        }
    }

    /**
     * Get session key for a given session ID
     *
     * Generates consistent Redis key format: "sessions:sessionId"
     * Used by both session handler and cron job for consistency.
     */
    public static function getSessionKey(string $sessionId): string
    {
        return self::SESSION_NAMESPACE . ':' . $sessionId;
    }

    /**
     * Get user sessions key for a given user ID
     *
     * Generates consistent Redis key format: "users:userId:sessions"
     * Used to maintain sets of session IDs for each user.
     */
    public static function getUserSessionKey(string $userId): string
    {
        return 'users:' . $userId . ':sessions';
    }

    /**
     * Get admin sessions key for a given admin ID
     *
     * Generates consistent Redis key format: "admins:adminId:sessions"
     * Used to maintain sets of session IDs for each admin.
     */
    public static function getAdminSessionKey(string $adminId): string
    {
        return 'admins:' . $adminId . ':sessions';
    }

    /**
     * Generate session timeout string for given lifetime
     *
     * @param int $lifetime Session lifetime in seconds
     * @return string ISO8601 formatted timeout string
     */
    public static function getSessionTimeout(int $lifetime): string
    {
        return Carbon::now()->addSeconds($lifetime)->toIso8601String();
    }

    /**
     * Check if a session is expired based on its payload
     *
     * @param array $sessionPayload Session data containing timeout information
     * @return bool True if session is expired, false otherwise
     */
    public static function isSessionExpired(array $sessionPayload): bool
    {
        if (empty($sessionPayload) || !isset($sessionPayload[self::SESSION_TIMEOUT_KEY])) {
            // If timeout cannot be found in payload, consider session expired for security
            // ideally this should never happen, but in case then consider it as expired
            return true;
        }

        try {
            $sessionTimeout = Carbon::parse($sessionPayload[self::SESSION_TIMEOUT_KEY]);
            $currentTime = Carbon::now();

            return $currentTime->greaterThan($sessionTimeout);
        } catch (Exception $e) {
            // Log the error with detailed information for debugging
            app('trace')->error(TraceCode::SESSION_TIMEOUT_PARSE_FAILED, [
                'session_timeout' => $sessionTimeout,
                'error_message' => $e->getMessage(),
                'session_payload_keys' => array_keys($sessionPayload),
            ]);

            // If timeout cannot be parsed, consider session expired for security
            // ideally this should never happen, but in case then consider it as expired
            return true;
        }
    }

    /**
     * Get session IDs from Memory DB set (for admin/user session lists)
     *
     * This method retrieves all session IDs from a Memory DB set:
     * - Used for admin session lists: admins:adminId:sessions
     * - Used for user session lists: users:userId:sessions
     * - Provides error handling and observability
     *
     * @param string $setKey Redis set key (admin or user sessions set)
     * @return array Array of session IDs
     */
    public static function getSessionIdsFromMemoryDb(string $setKey): array
    {
        $memoryDb = new MemoryDbConnection();

        try {
            return $memoryDb->smembers($setKey, SessionConstants::CONTEXT_REDIS_SET_OPERATIONS);
        } catch (\Exception $e) {
            app('trace')->error(TraceCode::SESSION_MEMORY_DB_READ_FAILED, [
                'set_key' => $setKey,
                'error' => $e->getMessage(),
                'context' => 'SessionUtils::getSessionIdsFromMemoryDb'
            ]);
            // Return empty array on error - don't fail the entire operation
            return [];
        }
    }
}
