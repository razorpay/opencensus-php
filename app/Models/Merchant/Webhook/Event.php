<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Product;
use RZP\Models\FundAccount;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
class Event
{
    const PAYMENT_AUTHORIZED                = 'payment.authorized';
    const PAYMENT_FAILED                    = 'payment.failed';
    const PAYMENT_CAPTURED                  = 'payment.captured';
    const PAYMENT_DISPUTE_CREATED           = 'payment.dispute.created';
    const ORDER_PAID                        = 'order.paid';
    const INVOICE_PAID                      = 'invoice.paid';
    const INVOICE_PARTIALLY_PAID            = 'invoice.partially_paid';
    const INVOICE_EXPIRED                   = 'invoice.expired';
    const VPA_EDITED                        = 'vpa.edited';
    const P2P_CREATED                       = 'p2p.created';
    const P2P_REJECTED                      = 'p2p.rejected';
    const P2P_TRANSFERRED                   = 'p2p.transferred';
    const SUBSCRIPTION_ACTIVATED            = 'subscription.activated';
    const SUBSCRIPTION_CHARGED              = 'subscription.charged';
    const SUBSCRIPTION_PENDING              = 'subscription.pending';
    const SUBSCRIPTION_HALTED               = 'subscription.halted';
    const SUBSCRIPTION_CANCELLED            = 'subscription.cancelled';
    const SUBSCRIPTION_COMPLETED            = 'subscription.completed';
    const SUBSCRIPTION_UPDATED              = 'subscription.updated';
    const TOKEN_CONFIRMED                   = 'token.confirmed';
    const TOKEN_REJECTED                    = 'token.rejected';
    const SETTLEMENT_PROCESSED              = 'settlement.processed';
    const VIRTUAL_ACCOUNT_CREDITED          = 'virtual_account.credited';
    const VIRTUAL_ACCOUNT_CREATED           = 'virtual_account.created';
    const PAYMENT_DISPUTE_WON               = 'payment.dispute.won';
    const PAYMENT_DISPUTE_LOST              = 'payment.dispute.lost';
    const PAYMENT_DISPUTE_CLOSED            = 'payment.dispute.closed';
    const TRANSACTION_CREATED               = 'transaction.created';
    const PAYOUT_CREATED                    = 'payout.created';
    const PAYOUT_PROCESSED                  = 'payout.processed';
    const PAYOUT_REVERSED                   = 'payout.reversed';
    const FUND_ACCOUNT_VALIDATION_COMPLETED = 'fund_account.validation.completed';
    const PAYMENT_DOWNTIME_STARTED          = 'payment.downtime.started';
    const PAYMENT_DOWNTIME_RESOLVED         = 'payment.downtime.resolved';
    const PAYOUT_QUEUED                     = 'payout.queued';
    const PAYOUT_INITIATED                  = 'payout.initiated';
    const REFUND_SPEED_CHANGED              = 'refund.speed_changed';
    const REFUND_PROCESSED                  = 'refund.processed';
    const REFUND_FAILED                     = 'refund.failed';
    const PAYOUT_FAILED                     = 'payout.failed';

    protected static $events = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::PAYMENT_DISPUTE_CREATED,
        self::ORDER_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        self::SUBSCRIPTION_UPDATED,
        self::TOKEN_CONFIRMED,
        self::TOKEN_REJECTED,
        self::SETTLEMENT_PROCESSED,
        self::VIRTUAL_ACCOUNT_CREDITED,
        self::VIRTUAL_ACCOUNT_CREATED,
        self::PAYMENT_DISPUTE_WON,
        self::PAYMENT_DISPUTE_LOST,
        self::PAYMENT_DISPUTE_CLOSED,
        self::TRANSACTION_CREATED,
        self::PAYOUT_CREATED,
        self::PAYOUT_PROCESSED,
        self::PAYOUT_REVERSED,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED,
        self::PAYMENT_DOWNTIME_STARTED,
        self::PAYMENT_DOWNTIME_RESOLVED,
        self::PAYOUT_QUEUED,
        self::PAYOUT_INITIATED,
        self::REFUND_SPEED_CHANGED,
        self::REFUND_PROCESSED,
        self::REFUND_FAILED,
        self::PAYOUT_FAILED
    ];

    /**
     * Events which are present in the system and
     * can be enabled/disabled.
     * @var array
     */
    protected static $names = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::PAYMENT_DISPUTE_CREATED,
        self::ORDER_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        self::SUBSCRIPTION_UPDATED,
        self::TOKEN_CONFIRMED,
        self::TOKEN_REJECTED,
        self::SETTLEMENT_PROCESSED,
        self::VIRTUAL_ACCOUNT_CREDITED,
        self::VIRTUAL_ACCOUNT_CREATED,
        self::PAYMENT_DISPUTE_WON,
        self::PAYMENT_DISPUTE_LOST,
        self::PAYMENT_DISPUTE_CLOSED,
        self::TRANSACTION_CREATED,
        self::PAYOUT_CREATED,
        self::PAYOUT_PROCESSED,
        self::PAYOUT_REVERSED,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED,
        self::PAYMENT_DOWNTIME_STARTED,
        self::PAYMENT_DOWNTIME_RESOLVED,
        self::PAYOUT_QUEUED,
        self::PAYOUT_INITIATED,
        self::REFUND_SPEED_CHANGED,
        self::REFUND_PROCESSED,
        self::REFUND_FAILED,
        self::PAYOUT_FAILED
    ];

    protected static $bitPosition = [
        self::PAYMENT_AUTHORIZED                => 1,
        self::PAYMENT_FAILED                    => 2,
        self::PAYMENT_CAPTURED                  => 3,
        self::ORDER_PAID                        => 4,
        self::INVOICE_PAID                      => 5,
        self::VPA_EDITED                        => 6,
        self::P2P_CREATED                       => 7,
        self::P2P_REJECTED                      => 8,
        self::P2P_TRANSFERRED                   => 9,
        self::SUBSCRIPTION_ACTIVATED            => 10,
        self::SUBSCRIPTION_PENDING              => 11,
        self::SUBSCRIPTION_HALTED               => 12,
        self::SUBSCRIPTION_CHARGED              => 13,
        self::SUBSCRIPTION_CANCELLED            => 14,
        self::SUBSCRIPTION_COMPLETED            => 15,
        // self::SUBSCRIPTION_EXPIRED              => 16,
        self::INVOICE_EXPIRED                   => 17,
        self::INVOICE_PARTIALLY_PAID            => 18,
        self::TOKEN_CONFIRMED                   => 19,
        self::TOKEN_REJECTED                    => 20,
        self::SETTLEMENT_PROCESSED              => 21,
        self::PAYMENT_DISPUTE_CREATED           => 22,
        self::VIRTUAL_ACCOUNT_CREDITED          => 23,
        self::VIRTUAL_ACCOUNT_CREATED           => 24,
        self::PAYMENT_DISPUTE_WON               => 25,
        self::PAYMENT_DISPUTE_LOST              => 26,
        self::PAYMENT_DISPUTE_CLOSED            => 27,
        self::TRANSACTION_CREATED               => 28,
        self::PAYOUT_CREATED                    => 29,
        self::PAYOUT_PROCESSED                  => 30,
        self::PAYOUT_REVERSED                   => 31,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => 32,
        self::PAYMENT_DOWNTIME_STARTED          => 33,
        self::PAYMENT_DOWNTIME_RESOLVED         => 34,
        self::PAYOUT_QUEUED                     => 35,
        self::PAYOUT_INITIATED                  => 36,
        self::SUBSCRIPTION_UPDATED              => 37,
        self::REFUND_SPEED_CHANGED              => 38,
        self::REFUND_PROCESSED                  => 39,
        self::REFUND_FAILED                     => 40,
        self::PAYOUT_FAILED                     => 41,
    ];

    /**
     * These are events which will displayed to merchants
     * for enabling/disabling.
     * @var array
     */
    protected static $launchedEvents = [
        self::PAYMENT_AUTHORIZED                => [Product::PRIMARY],
        self::PAYMENT_FAILED                    => [Product::PRIMARY],
        self::PAYMENT_CAPTURED                  => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_CREATED           => [Product::PRIMARY],
        self::ORDER_PAID                        => [Product::PRIMARY],
        self::INVOICE_PAID                      => [Product::PRIMARY],
        self::INVOICE_PARTIALLY_PAID            => [Product::PRIMARY],
        self::INVOICE_EXPIRED                   => [Product::PRIMARY],
        self::SUBSCRIPTION_ACTIVATED            => [Product::PRIMARY],
        self::SUBSCRIPTION_PENDING              => [Product::PRIMARY],
        self::SUBSCRIPTION_HALTED               => [Product::PRIMARY],
        self::SUBSCRIPTION_CHARGED              => [Product::PRIMARY],
        self::SUBSCRIPTION_CANCELLED            => [Product::PRIMARY],
        self::SUBSCRIPTION_COMPLETED            => [Product::PRIMARY],
        self::SUBSCRIPTION_UPDATED              => [Product::PRIMARY],
        self::TOKEN_CONFIRMED                   => [Product::PRIMARY],
        self::TOKEN_REJECTED                    => [Product::PRIMARY],
        self::SETTLEMENT_PROCESSED              => [Product::PRIMARY],
        self::VIRTUAL_ACCOUNT_CREDITED          => [Product::PRIMARY],
        self::VIRTUAL_ACCOUNT_CREATED           => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_WON               => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_LOST              => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_CLOSED            => [Product::PRIMARY],
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => [Product::PRIMARY],
        self::TRANSACTION_CREATED               => [Product::BANKING],
        self::PAYOUT_CREATED                    => [Product::PRIMARY, Product::BANKING],
        self::PAYOUT_PROCESSED                  => [Product::PRIMARY, Product::BANKING],
        self::PAYOUT_REVERSED                   => [Product::PRIMARY, Product::BANKING],
        self::PAYMENT_DOWNTIME_STARTED          => [Product::PRIMARY],
        self::PAYMENT_DOWNTIME_RESOLVED         => [Product::PRIMARY],
        self::PAYOUT_QUEUED                     => [Product::BANKING],
        self::PAYOUT_INITIATED                  => [Product::PRIMARY, Product::BANKING],
        self::REFUND_SPEED_CHANGED              => [Product::PRIMARY],
        self::REFUND_PROCESSED                  => [Product::PRIMARY],
        self::REFUND_FAILED                     => [Product::PRIMARY],
        self::PAYOUT_FAILED                     => [Product::BANKING],
    ];

    /**
     * Defines the mapping to main entity for respective event and also
     * the field description to be set in mail content for webhook related mails
     *
     * @var array
     */
    public static $eventsToEntityMap = [
        self::PAYMENT_AUTHORIZED                => Entity::PAYMENT,
        self::PAYMENT_CAPTURED                  => Entity::PAYMENT,
        self::PAYMENT_FAILED                    => Entity::PAYMENT,
        self::PAYMENT_DISPUTE_CREATED           => Entity::PAYMENT,
        self::VIRTUAL_ACCOUNT_CREDITED          => Entity::PAYMENT,
        self::VIRTUAL_ACCOUNT_CREATED           => Entity::VIRTUAL_ACCOUNT,
        self::INVOICE_PAID                      => Entity::INVOICE,
        self::INVOICE_PARTIALLY_PAID            => Entity::INVOICE,
        self::INVOICE_EXPIRED                   => Entity::INVOICE,
        self::ORDER_PAID                        => Entity::ORDER,
        self::SUBSCRIPTION_ACTIVATED            => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_PENDING              => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_HALTED               => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_CHARGED              => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_CANCELLED            => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_COMPLETED            => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_UPDATED              => Entity::SUBSCRIPTION,
        self::TOKEN_CONFIRMED                   => Entity::TOKEN,
        self::TOKEN_REJECTED                    => Entity::TOKEN,
        self::SETTLEMENT_PROCESSED              => Entity::SETTLEMENT,
        self::PAYMENT_DISPUTE_WON               => Entity::DISPUTE,
        self::PAYMENT_DISPUTE_LOST              => Entity::DISPUTE,
        self::PAYMENT_DISPUTE_CLOSED            => Entity::DISPUTE,
        self::TRANSACTION_CREATED               => Entity::TRANSACTION,
        self::PAYOUT_CREATED                    => Entity::PAYOUT,
        self::PAYOUT_PROCESSED                  => Entity::PAYOUT,
        self::PAYOUT_REVERSED                   => Entity::PAYOUT,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => FundAccount\Validation\Entity::PUBLIC_ENTITY_NAME,
        self::PAYMENT_DOWNTIME_STARTED          => Entity::PAYMENT_DOWNTIME,
        self::PAYMENT_DOWNTIME_RESOLVED         => Entity::PAYMENT_DOWNTIME,
        self::PAYOUT_QUEUED                     => Entity::PAYOUT,
        self::PAYOUT_INITIATED                  => Entity::PAYOUT,
        self::REFUND_SPEED_CHANGED              => Entity::REFUND,
        self::REFUND_PROCESSED                  => Entity::REFUND,
        self::REFUND_FAILED                     => Entity::REFUND,
        self::PAYOUT_FAILED                     => Entity::PAYOUT,
    ];

    public static $eventsToFeatureMap = [
        self::SUBSCRIPTION_ACTIVATED            => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_PENDING              => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_HALTED               => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_CHARGED              => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_CANCELLED            => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_COMPLETED            => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_UPDATED              => Feature\Constants::SUBSCRIPTIONS,
        self::TOKEN_CONFIRMED                   => Feature\Constants::CHARGE_AT_WILL,
        self::TOKEN_REJECTED                    => Feature\Constants::CHARGE_AT_WILL,
        self::VIRTUAL_ACCOUNT_CREDITED          => Feature\Constants::VIRTUAL_ACCOUNTS,
        self::VIRTUAL_ACCOUNT_CREATED           => Feature\Constants::VIRTUAL_ACCOUNTS,
        self::SETTLEMENT_PROCESSED              => Feature\Constants::MARKETPLACE,
        self::PAYOUT_CREATED                    => Feature\Constants::PAYOUT,
        self::PAYOUT_PROCESSED                  => Feature\Constants::PAYOUT,
        self::PAYOUT_REVERSED                   => Feature\Constants::PAYOUT,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => Feature\Constants::FUND_ACCOUNT_VALIDATIONS,
        self::PAYOUT_QUEUED                     => Feature\Constants::PAYOUT,
        self::PAYOUT_INITIATED                  => Feature\Constants::PAYOUT,
        self::PAYMENT_DOWNTIME_STARTED          => Feature\Constants::EXPOSE_DOWNTIMES,
        self::PAYMENT_DOWNTIME_RESOLVED         => Feature\Constants::EXPOSE_DOWNTIMES,
        self::REFUND_SPEED_CHANGED              => Feature\Constants::CARD_TRANSFER_REFUND,
        self::REFUND_PROCESSED                  => Feature\Constants::CARD_TRANSFER_REFUND,
        self::REFUND_FAILED                     => Feature\Constants::SHOW_REFUND_PUBLIC_STATUS,
        self::PAYOUT_FAILED                     => Feature\Constants::PAYOUT,
    ];

    /**
     * Takes the hex value and merges it
     * with the hex value of the events passed.
     *
     * @param  array    $events
     * @param  integer  $hex
     * @return integer
     */
    public static function getHexValue($events, $hex)
    {
        foreach ($events as $event => $value)
        {
            $pos = Event::getBitPosition($event);

            $value = ($value === '1') ? 1 : 0;

            // Sets the bit value for the current event.
            $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));
        }

        return $hex;
    }

    public static function getAllEventNames()
    {
        return self::$names;
    }

    public static function getLaunchedEventNames()
    {
        return self::$launchedEvents;
    }

    public static function getEnabledEvents($hex)
    {
        $events = [];

        foreach (self::$events as $event)
        {
            $pos = self::$bitPosition[$event];
            $value = ($hex >> ($pos - 1)) & 1;

            if ($value)
            {
                array_push($events, $event);
            }
        }

        return $events;
    }

    public static function isEventEnabled($hexEvent, $event)
    {
        $pos = self::getBitPosition($event);

        return ($hexEvent >> ($pos - 1)) & 1;
    }

    public static function validateEventName(string $event): bool
    {
        return (in_array($event, self::$names) === true);
    }

    public static function getBitPosition(string $event): int
    {
        return self::$bitPosition[$event];
    }


    /**
     * Filters and returns events to be exposed in public api response.
     *
     * @param  Merchant\Entity $merchant
     * @param  array|null      $events
     * @return array
     */
    public static function filterForPublicApi(Merchant\Entity $merchant, array $events = null)
    {
        $originalEvents = ($events !== null) ?
                          $events :
                          static::getLaunchedEventNames();

        $productFilteredEvents = static::filterByProductOrigin($originalEvents);

        $featureFilteredEvents = static::filterByFeatures($productFilteredEvents, $merchant->getEnabledFeatures());

        return $featureFilteredEvents;
    }

    public static function filterByProductOrigin(array $events): array
    {
        $product = app('basicauth')->getRequestOriginProduct() ?? Product::PRIMARY;

        $productEvents = group_array_by_value_array($product, self::getLaunchedEventNames())[$product];

        return array_filter(
            $events,
            function($event) use ($productEvents)
            {
                return (in_array($event, $productEvents, true) === true);
            }, ARRAY_FILTER_USE_KEY);
    }

    public static function filterByFeatures(array $eventNames, array $merchantAssignedFeatures): array
    {
        $featureMap = Event::$eventsToFeatureMap;

        foreach ($eventNames as $eventName => $value)
        {
            if ((isset($featureMap[$eventName]) === true) and
                (in_array($featureMap[$eventName], $merchantAssignedFeatures, true) === false))
            {
                unset($eventNames[$eventName]);
            }
        }

        return $eventNames;
    }
}
