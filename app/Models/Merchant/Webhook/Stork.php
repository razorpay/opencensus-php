<?php

namespace RZP\Models\Merchant\Webhook;


use Carbon\Carbon;

use RZP\Models\Event;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Jobs\WebhookEvent;
use RZP\Constants\Product;
use RZP\Constants\Entity as E;

/**
 * Class Stork
 *
 * Holds implementation for various communication to stork service
 * against webhook module of api. E.g. dual writing webhook etc.
 *
 * @package RZP\Models\Merchant\Webhook
 * @see \RZP\Services\Stork
 */
class Stork
{
    /**
     * @var \RZP\Services\Stork
     */
    protected $service;

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * Product value is used to figure out service value to use for stork communication.
     * @var string
     */
    protected $product;

    // Just few literals used in request/response.
    const SERVICE    = 'service';
    const OWNER_ID   = 'owner_id';
    const OWNER_TYPE = 'owner_type';
    const MERCHANT   = 'merchant';
    const WEBHOOK_ID = 'webhook_id';
    const LIMIT      = 'limit';

    public function __construct(string $product = Product::PRIMARY)
    {
        $this->service = app('stork_service');
        $this->trace   = app('trace');
        $this->product = $product;
    }

    /**
     * @param Entity $webhook
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function create(Entity $webhook)
    {
        // Todo: Add Base\Entity::getMode() method. getConnectionName() may
        // return slave-live or slave-test. But in create and update it will not
        // because writes do not go to master.
        $this->service->init($webhook->merchant->getConnectionName(), $this->product);

        $res = $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            [
                'webhook' => $this->serializeWebhook($webhook),
            ]);

        return json_decode($res->body, true) ?: [];
    }

    /**
     * @param Entity $webhook
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function update(Entity $webhook)
    {
        $this->service->init($webhook->merchant->getConnectionName(), $this->product);

        $res = $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            [
                'webhook' => $this->serializeWebhook($webhook),
            ]);

        return json_decode($res->body, true) ?: [];
    }

    /**
     * @param Entity $webhook
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function upsert(Entity $webhook)
    {
        // Stork's update rpc itself handles upsert behavior.
        return $this->update($webhook);
    }

    /**
     * # What?
     * Calls processEvent() and if failure queues it for which worker exists in
     * this service itself. The worker again just calls processEvent() for each
     * queued messages.
     *
     * # Why?
     * We are doing this to avoid event drops with network issues and/or
     * timeouts between api<>stork communication. Note that there exists retry
     * for http call and this is eventual fallback.
     *
     * Worker exists for now in api service itself to save development time and
     * devops ask. Ideally there should be a shared queue and stork itself
     * should drain that queue.
     *
     * @param Event\Entity $event
     * @param string       $mode
     *
     * @return void
     */
    public function processEventSafe(Event\Entity $event, string $mode)
    {
        try
        {
            $this->processEvent($event, $mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::STORK_DISPATCH_EVENT_FAILED);

            // Exception for this call i.e. dispatch() is suppressed and logged within by the dispatcher.
            WebhookEvent::dispatch($mode, $event->merchant, $event->getAttributes(), $this->product);
        }
    }

    /**
     * Calls rzp.stork.webhook.v1.WebhookAPI/ProcessEvent endpoint of stork service.
     * Also see processEventSafe().
     *
     * @param Event\Entity $event
     * @param string       $mode
     *
     * @return void
     * @throws \RZP\Exception\ServerErrorException
     * @throws \Throwable
     */
    public function processEvent(Event\Entity $event, string $mode)
    {
        $this->trace->info(TraceCode::STORK_DISPATCH_EVENT_REQUEST, $event->toArrayPublic());

        $this->service->init($mode, $this->product);

        $merchant = $event->merchant;

        $payload = json_encode($event->toArrayPublic());

        if (empty($merchant) === false)
        {
            $response = (new Merchant\Core)->translateWebhookPayloadIfApplicable($merchant, $payload, $mode);

            $payload  = $response['content'];
        }

        $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent',
            [
                'event' => [
                    'service'    => $this->service->service,
                    'owner_id'   => $event->getMerchantId(),
                    'owner_type' => E::MERCHANT,
                    'name'       => $event->event,
                    'payload'    => $payload,
                ],
            ]);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string          $webhookId
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function fetch(Merchant\Entity $merchant, string $webhookId)
    {
        $this->service->init($merchant->getConnectionName(), $this->product);

        $res = $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get',
            [
                self::WEBHOOK_ID => $webhookId,
                self::OWNER_ID   => $merchant->getId(),
                self::OWNER_TYPE => 'merchant',
            ]);

        return json_decode($res->body, true) ?: [];
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function fetchMultiple(Merchant\Entity $merchant)
    {
        $this->service->init($merchant->getConnectionName(), $this->product);

        $res = $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            [
                self::SERVICE    => $this->service->service,
                self::OWNER_ID   => $merchant->getId(),
                self::OWNER_TYPE => self::MERCHANT,
                self::LIMIT      => 1,
            ]);

        return json_decode($res->body, true) ?: [];
    }

    public function invalidateCacheForBothModeWithoutFail(string $merchantId = null)
    {
        if ($merchantId !== null)
        {
            (new self)->invalidateCacheWithoutFail($merchantId, 'live');
            (new self)->invalidateCacheWithoutFail($merchantId, 'test');
        }
    }

    public function invalidateCacheWithoutFail(string $merchantId, string $mode)
    {
        $maxAttempts = 2;
        while ($maxAttempts--)
        {
            try
            {
                $this->invalidateCache($merchantId, $mode);
                return;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }
    }

    public function invalidateCache(string $merchantId, string $mode)
    {
        $this->service->init($mode, $this->product);

        $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/InvalidateCache',
            [
                'service'    => $this->service->service,
                'owner_id'   => $merchantId,
                'owner_type' => E::MERCHANT,
                'prefix'     => 'affected-owners',
            ]);
    }

    protected function serializeWebhook(Entity $webhook): array
    {

        return [
            'id'            => $webhook->getId(),
            'service'       => $this->service->service,
            'owner_id'      => $webhook->getEntityId() ?: $webhook->getMerchantId(),
            'owner_type'    => $webhook->getEntityType() ?: E::MERCHANT,
            'disabled'      => $webhook->isActive() === false,
            'url'           => $webhook->getUrl(),
            'secret'        => $webhook->getSecret(),
            'subscriptions' => ($this->product === Product::PRIMARY) ? $this->getPrimaryProductSubscriptions($webhook)
                                                                     : $this->getBankingProductSubscriptions($webhook),
        ];
    }

    protected function getBankingProductSubscriptions(Entity $webhook)
    {
        $events = \RZP\Models\Merchant\Webhook\Event::getAllEventsByProduct(Product::BANKING);

        return array_map(
            function($v)
            {
                return ['eventmeta' => ['name' => $v]];
            },
            array_keys(
                array_filter($webhook->getEvents(),
                    function($value, $event) use ($events)
                    {
                        if (in_array($event, $events) and ($value === true))
                        {
                            return true;
                        }
                        return false;
                    }, ARRAY_FILTER_USE_BOTH)
            ));
    }

    protected function getPrimaryProductSubscriptions(Entity $webhook)
    {
        return array_map(
            function($v)
            {
                return ['eventmeta' => ['name' => $v]];
            },
            array_keys(array_filter($webhook->getEvents())));
    }

    public function deserializeStorkWebhook(array $response) : array
    {
        $response = $this->convertSubscriptionToEventsArray($response);

        return [
            'active' => ((isset($response['disabled']) and $response['disabled'] === true) ? '0' : '1'),
            'url' => $response['url'],
            'events' => $response['events'],
        ];
    }

    protected function convertSubscriptionToEventsArray(array $res): array
    {
        $events = array_map(function ($v) { return $v['eventmeta']['name']; }, $res['subscriptions']);

        $eventInput = [];
        foreach ($events as $event)
        {
            $eventInput[$event] = '1';
        }

        $res['events'] = $eventInput;

        return $res;
    }
}
