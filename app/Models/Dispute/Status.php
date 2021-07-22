<?php

namespace RZP\Models\Dispute;

use RZP\Models\Merchant\Webhook\Event as WebhookEvent;

class Status
{
    const OPEN         = 'open';
    const UNDER_REVIEW = 'under_review';
    const LOST         = 'lost';
    const WON          = 'won';
    const CLOSED       = 'closed';

    protected static $closedStatuses = [
        self::WON,
        self::LOST,
        self::CLOSED,
    ];

    protected static $openStatuses = [
        self::OPEN,
        self::UNDER_REVIEW
    ];

    /**
     * $transactionalStatuses are statuses where adjustment transactions
     * should be done when a dispute reaches one of these statuses
     */
    protected static $transactionalStatuses = [
        self::WON,
        self::LOST,
    ];

    protected static $merchantAcceptedStatuses = [
        self::LOST,
        self::CLOSED,
    ];

    public static $webhookEventMap = [
        self::WON          => WebhookEvent::PAYMENT_DISPUTE_WON,
        self::LOST         => WebhookEvent::PAYMENT_DISPUTE_LOST,
        self::CLOSED       => WebhookEvent::PAYMENT_DISPUTE_CLOSED,
        self::UNDER_REVIEW => WebhookEvent::PAYMENT_DISPUTE_UNDER_REVIEW,
        self::OPEN         => WebhookEvent::PAYMENT_DISPUTE_ACTION_REQUIRED,
    ];

    public static function exists(string $status): bool
    {
        return defined(get_class() . '::' . strtoupper($status));
    }

    public static function getClosedStatuses(): array
    {
        return self::$closedStatuses;
    }

    public static function getOpenStatuses(): array
    {
        return self::$openStatuses;
    }

    public static function getTransactionalStatuses(): array
    {
        return self::$transactionalStatuses;
    }

    public static function getMerchantAcceptedStatuses(): array
    {
        return self::$merchantAcceptedStatuses;
    }
}
