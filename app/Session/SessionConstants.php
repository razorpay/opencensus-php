<?php

namespace App\Session;

/**
 * SessionConstants - Centralized constants for session operations
 *
 * This class contains all constants used across session management operations
 * to ensure consistency and avoid hardcoded strings throughout the codebase.
 *
 * Used by:
 * - app/Session/Entity.php (session deletion operations)
 * - app/Session/CustomCacheBasedSessionHandler.php (session handler operations)
 * - app/Session/SessionUtils.php (utility operations)
 * - app/Cron/Jobs/SessionCleanup/SessionCleanupCronJob.php (cron cleanup operations)
 * - app/Redis/ObservableRedisConnection.php (Redis operations with observability)
 */
class SessionConstants
{
    /**
     * SESSION CLEANUP CONTEXTS
     *
     * These constants define the context/source of session cleanup operations
     * for better traceability and monitoring during Redis to Memory DB migration.
     */

    /**
     * Context: Admin bulk session deletion
     * Used when: Admin deletes all other sessions except current one
     * Triggered by: Admin dashboard "Sign out other devices" functionality or similar
     */
    public const CONTEXT_ADMIN_DELETE_ALL_SESSIONS = 'ADMIN_DELETE_ALL_SESSIONS';

    /**
     * Context: User bulk session deletion
     * Used when: All user sessions are deleted
     * Triggered by: User removed from merchant team, bulk session cleanup
     */
    public const CONTEXT_USER_DELETE_ALL_SESSIONS = 'USER_DELETE_ALL_SESSIONS';

    /**
     * Context: User current session deletion
     * Used when: Current user session is specifically deleted
     * Triggered by: Edge team session revocation, authentication failures
     */
    public const CONTEXT_USER_DELETE_CURRENT_SESSION = 'USER_DELETE_CURRENT_SESSION';

    /**
     * Context: Admin specific session deletion
     * Used when: A specific admin session is deleted by ID
     * Triggered by: Admin dashboard session management, security actions
     */
    public const CONTEXT_ADMIN_DELETE_SPECIFIC_SESSION = 'ADMIN_DELETE_SPECIFIC_SESSION';

    /**
     * Context: Session handler operations
     * Used when: Laravel session handler performs operations
     * Triggered by: Normal session lifecycle (create, destroy)
     */
    public const CONTEXT_SESSION_HANDLER = 'SESSION_HANDLER';

    /**
     * Context: Cron job operations
     * Used when: Automated cron job cleans up expired sessions
     * Triggered by: Scheduled session cleanup tasks
     */
    public const CONTEXT_CRON_CLEANUP = 'SESSION_CRON_CLEANUP';

    /**
     * REDIS OPERATION CONTEXTS
     *
     * These constants define the context/type of Redis operations performed
     * through ObservableRedisConnection for better monitoring and categorization.
     */

    /**
     * Context: Redis session read operations
     * Used when: Reading session data from Redis/Memory DB
     * Operations: GET, HGETALL, HMGET, etc.
     */
    public const CONTEXT_REDIS_SESSION_READ = 'REDIS_SESSION_READ';

    /**
     * Context: Redis session write operations
     * Used when: Writing session data to Redis/Memory DB
     * Operations: SET, HSET, HMSET, etc.
     */
    public const CONTEXT_REDIS_SESSION_WRITE = 'REDIS_SESSION_WRITE';

    /**
     * Context: Redis session delete operations
     * Used when: Deleting session data from Redis/Memory DB
     * Operations: DEL, HDEL, etc.
     */
    public const CONTEXT_REDIS_SESSION_DELETE = 'REDIS_SESSION_DELETE';

    /**
     * Context: Redis set operations
     * Used when: Working with Redis sets (user/admin session lists)
     * Operations: SADD, SREM, SMEMBERS, SCARD, etc.
     */
    public const CONTEXT_REDIS_SET_OPERATIONS = 'REDIS_SET_OPERATIONS';

    /**
     * Context: Redis hash operations
     * Used when: Working with Redis hashes (session data storage)
     * Operations: HGETALL, HMSET, HGET, HSET, etc.
     */
    public const CONTEXT_REDIS_HASH_OPERATIONS = 'REDIS_HASH_OPERATIONS';

    /**
     * Context: Generic Redis operations
     * Used when: Performing general Redis operations not covered by specific contexts
     * Operations: EXISTS, EXPIRE, TTL, etc.
     */
    public const CONTEXT_REDIS_GENERIC_OPERATIONS = 'REDIS_GENERIC_OPERATIONS';

    /**
     * Context: General session deletion operations
     * Used when: Performing session deletion from any source
     * Operations: Session cleanup, deletion from Entity class
     */
    public const CONTEXT_SESSION_DELETE = 'SESSION_DELETE';

    /**
     * SESSION RELATION ACTIONS
     *
     * These constants define specific actions performed on session relations
     * (user/admin session sets) for detailed operation tracking.
     */

    /**
     * Action: Admin session relation deletion from Memory DB set
     * Used when: Removing session ID from admin sessions set in Memory DB
     * Operation: SREM admins:adminId:sessions sessionId
     */
    public const ACTION_ADMIN_SESSION_DELETE_FROM_MEMORY_DB_SET = 'ADMIN_SESSION_DELETE_FROM_MEMORY_DB_SET';

    /**
     * Action: User session relation deletion from Memory DB set
     * Used when: Removing session ID from user sessions set in Memory DB
     * Operation: SREM users:userId:sessions sessionId
     */
    public const ACTION_USER_SESSION_DELETE_FROM_MEMORY_DB_SET = 'USER_SESSION_DELETE_FROM_MEMORY_DB_SET';

    /**
     * Action: Generic session relation deletion
     * Used when: Default action for session relation operations
     * Operation: Generic SREM operations on session sets
     */
    public const ACTION_SESSION_RELATION_DELETE = 'SESSION_RELATION_DELETE';

    /**
     * DEFAULT VALUES
     *
     * Default constants used when no specific context/action is provided
     */

    /**
     * Default context for SessionUtils operations
     */
    public const DEFAULT_CONTEXT = 'SESSION_UTILS';

    /**
     * Default action for session relation operations
     */
    public const DEFAULT_ACTION = self::ACTION_SESSION_RELATION_DELETE;

    /**
     * SESSION STORAGE TYPES
     *
     * These constants define the different storage backends used for session data
     * based on cross-region requirements.
     */

    /**
     * Storage Type: Redis
     * Used when: Same-region requests (merchant region == cell region)
     * Storage: Local Redis instance
     */
    public const STORAGE_TYPE_REDIS = 'redis';

    /**
     * Storage Type: Memory Database
     * Used when: Cross-region requests (merchant region != cell region)
     * Storage: Shared Memory Database accessible across regions
     */
    public const STORAGE_TYPE_MEMORY_DB = 'memory_db';

    // Decision Reasons
    public const DECISION_REASON_CROSS_REGION_COOKIE_SET = 'cross_region_cookie_set';
    public const DECISION_REASON_CROSS_REGION_CONTEXT_WITHOUT_COOKIE = 'cross_region_context_without_cookie';
    public const DECISION_REASON_SAME_REGION_DEFAULT = 'same_region_default';

    // Storage Strategy Array Keys
    public const STRATEGY_KEY_STORAGE_TYPE = 'storage_type';
    public const STRATEGY_KEY_REQUIRES_MIGRATION = 'requires_migration';
    public const STRATEGY_KEY_DECISION_REASON = 'decision_reason';
}
