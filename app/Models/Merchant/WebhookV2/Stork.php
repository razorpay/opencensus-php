<?php

namespace RZP\Models\Merchant\WebhookV2;

use Razorpay\Trace\Logger;

use RZP\Models\Event;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Jobs\WebhookEvent;
use RZP\Constants\Entity as E;

/**
 * Class Stork
 *
 * Holds implementation for various communication to stork service
 * against webhook module of api. E.g. dual writing webhook etc.
 *
 * @package RZP\Models\Merchant\WebhookV2
 * @see \RZP\Services\Stork
 */
class Stork
{
    // Calls to stork defaults to 2s timeout, but for process event it is overridden to 350ms.
    const PROCESS_EVENT_REQUEST_TIMEOUT_MS = 350;
    const REPLAY_EVENT_REQUEST_TIMEOUT_MS  = 2000;

    /**
     * @var string
     */
    protected $mode;

    /**
     * Product value is used to figure out service value to use for stork communication.
     * @var string
     */
    protected $product;

    /**
     * @var \RZP\Services\Stork
     */
    protected $service;

    /**
     * @var Logger
     */
    protected $trace;

    const WK_GET_ROUTE                = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get';
    const WK_LIST_ROUTE               = '/twirp/rzp.stork.webhook.v1.WebhookAPI/List';
    const WK_DELETE_ROUTE             = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Delete';
    const WK_EDIT_ROUTE               = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update';
    const WK_CREATE_ROUTE             = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create';
    const WK_GET_WITH_SECRET_ROUTE    = '/twirp/rzp.stork.webhook.v1.WebhookAPI/GetWithSecret';
    const WK_LIST_WITH_SECRET_ROUTE   = '/twirp/rzp.stork.webhook.v1.WebhookAPI/ListWithSecret';
    const WK_GET_ANALYTICS_ROUTE      = '/twirp/rzp.stork.webhook.v1.WebhookAPI/GetAnalytics';
    const WK_LIST_EVENTS_ROUTE        = '/twirp/rzp.stork.webhook.v1.WebhookAPI/ListWebhookEvents';

    public function __construct(string $mode = Mode::LIVE, string $product = Product::PRIMARY)
    {
        $this->mode    = $mode;
        $this->product = $product;
        $this->service = app('stork_service');
        $this->trace   = app('trace');

        $this->service->init($this->mode, $this->product);
    }

    /**
     * Makes a request to stork to create a webhook
     *
     * @param  array   $storkCreateInput The payload to send to stork for creating the webhook
     * @return array                     json body of webhook create response from Stork
     */
    public function create(array $input): array
    {
        $input['service'] = $this->service->service;
        $input['context'] = json_decode ('{}');

        $storkInput = ['webhook' => $input];

        $res = $this->service->request(self::WK_CREATE_ROUTE, $storkInput);

        $res = json_decode($res->body, true);

        if (isset($res['webhook']) === true)
        {
            $res = $this->formatWebhook($res['webhook']);
        }

        return $res;
    }

    public function edit(array $input): array
    {
        $input['service'] = $this->service->service;
        $input['context'] = json_decode ('{}');

        $storkInput = ['webhook' => $input];

        $res = $this->service->request(self::WK_EDIT_ROUTE, $storkInput);

        $res = json_decode($res->body, true);

        if (isset($res['webhook']) === true)
        {
            $res = $this->formatWebhook($res['webhook']);
        }

        return $res;
    }

    public function get(string $webhookId, string $ownerId): array
    {
        $input = [];
        $input['service']    = $this->service->service;
        $input['owner_id']   = $ownerId;
        $input['webhook_id'] = $webhookId;

        $res = $this->service->request(self::WK_GET_ROUTE, $input);
        $res = json_decode($res->body, true);

        if (isset($res['webhook']) === true)
        {
            $res = $this->formatWebhook($res['webhook']);
        }

        return $res ?? [];
    }

    //used for hosted applications
    public function getWithSecret(string $webhookId, string $ownerId): array
    {
        $input = [];
        $input['service']    = $this->service->service;
        $input['owner_id']   = $ownerId;
        $input['webhook_id'] = $webhookId;

        $res = $this->service->request(self::WK_GET_WITH_SECRET_ROUTE, $input);
        $res = json_decode($res->body, true);

        if (isset($res['webhook']) === true)
        {
            $res = $this->formatWebhook($res['webhook']);
        }

        return $res ?? [];
    }

    public function list(string $ownerId, array $input = []): array
    {
        $input['service'] = $this->service->service;
        $input['owner_id'] = $ownerId;

        // Adding pagination params of stork
        $input['limit'] = $input['limit'] ?? $input['count'] ?? 10;
        $input['offset'] = $input['offset'] ?? $input['skip'] ?? 0;

        $res = $this->service->request(self::WK_LIST_ROUTE, $input);
        $res = json_decode($res->body, true);

        $items = [];
        if (isset($res['webhooks']) === true)
        {
            $items = array_map(function ($v) { return $this->formatWebhook($v); }, $res['webhooks']);
        }

        return [
            'entity' => 'collection',
            'count'  => count($items),
            'items'  => $items,
        ];
    }

    //used for hosted applications
    public function listWithSecret(string $ownerId, array $input = []): array
    {
        $input['service'] = $this->service->service;
        $input['owner_id'] = $ownerId;

        $res = $this->service->request(self::WK_LIST_WITH_SECRET_ROUTE, $input);
        $res = json_decode($res->body, true);

        $items = [];

        if (isset($res['webhooks']) === true)
        {
            $items = array_map(function ($v) { return $this->formatWebhook($v); }, $res['webhooks']);
        }

        return [
            'entity' => 'collection',
            'count'  => count($items),
            'items'  => $items,
        ];
    }

    public function delete(string $webhookId, string $ownerId)
    {
        $input = [];
        $input['service']    = $this->service->service;
        $input['owner_id']   = $ownerId;
        $input['webhook_id'] = $webhookId;

        $this->service->request(self::WK_DELETE_ROUTE, $input);
    }

    public function getAnalytics(array $payload)
    {
        $response = $this->service->request(self::WK_GET_ANALYTICS_ROUTE, $payload);
        return json_decode($response->body, true) ?: [];
    }

    public function listWebhookEvents(array $payload)
    {
        $response = $this->service->request(self::WK_LIST_EVENTS_ROUTE, $payload);
        return json_decode($response->body, true) ?: [];
    }

    protected function formatWebhook(array $webhook)
    {
        if (isset($webhook['created_at']) === true)
        {
            $webhook['created_at'] = strtotime($webhook['created_at']);
        }

        if (isset($webhook['updated_at']) === true)
        {
            $webhook['updated_at'] = strtotime($webhook['updated_at']);
        }

        if (isset($webhook['disabled_at']) === true)
        {
            $webhook['disabled_at'] = strtotime($webhook['disabled_at']);
        }

        if (isset($webhook['subscriptions']) === false)
        {
            // setting as empty array to avoid null exceptions
            $webhook['subscriptions'] = [];
        }

        $webhook['subscriptions'] = array_map(function($v)
        {
            if (isset($v['created_at']) === true)
            {
                $v['created_at'] = strtotime($v['created_at']);
            }
            return $v;
        }, $webhook['subscriptions']);

        return $webhook;
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
     * @param String $ownerType
     * @return void
     */
    public function processEventSafe(Event\Entity $event, string $ownerType = E::MERCHANT)
    {
        try
        {
            $this->processEvent($event, $ownerType);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::STORK_DISPATCH_EVENT_FAILED);

            // Exception for this call i.e. dispatch() is suppressed and logged within by the dispatcher.
            WebhookEvent::dispatch($this->mode, $event->merchant, $event->getAttributes(), $this->product, $ownerType);
        }
    }

    /**
     * Calls rzp.stork.webhook.v1.WebhookAPI/ProcessEvent endpoint of stork service.
     * Also see processEventSafe().
     *
     * @param Event\Entity $event
     * @param String $ownerType
     * @return void
     * @throws \RZP\Exception\ServerErrorException
     * @throws \RZP\Exception\TwirpException
     * @throws \Throwable
     */
    public function processEvent(Event\Entity $event, string $ownerType = E::MERCHANT)
    {
        $merchant = $event->merchant;

        $payload = $event->toArrayPublic();

        $applicationId = null;

        if($ownerType === E::APPLICATION)
        {
            $applicationId = $this->extractAndRemoveApplicationFromPayloadIfApplicable( $payload);
        }

        $payload = json_encode($payload);

        if (empty($merchant) === false)
        {
            $response = (new Merchant\Core)->translateWebhookPayloadIfApplicable($merchant, $payload, $this->mode);

            $payload  = $response['content'];
        }

        $processEventReq = [
            'event' => [
                'id'         => $event->getId(),
                'service'    => $this->service->service,
                'owner_id'   => $ownerType === E::MERCHANT ? $event->getMerchantId() : $applicationId,
                'owner_type' => $ownerType,
                'name'       => $event->event,
                'payload'    => $payload,
            ],
        ];

        $eventTrace = $processEventReq;
        unset($eventTrace['event']['payload']);

        if ((isset($event->payload) === true) and
            (isset($event->payload['payment']) === true) and
            (isset($event->payload['payment']['entity']) === true) and
            (isset($event->payload['payment']['entity']['id']) === true))
        {
            $eventTrace['event']['payment_id'] = $event->payload['payment']['entity']['id'];
        }

        $this->trace->info(TraceCode::STORK_DISPATCH_EVENT_REQUEST, $eventTrace);

        $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent',
            $processEventReq,
            self::PROCESS_EVENT_REQUEST_TIMEOUT_MS
        );
    }

    /**
     * For webhooks where owner_type is application, we are initially passing application_id in payload
     * This function extracts & removes the application Id from the payload before we make a Stork request to trigger webhook.
     *
     * @param array $payload
     *
     * @return string
     */
    public function extractAndRemoveApplicationFromPayloadIfApplicable(array & $payload): string
    {
        $applicationId = null;

        if((isset($payload['payload']) === true)
            and (isset($payload['payload']['application_id']) === true))
        {
            $applicationId = $payload['payload']['application_id'];
            unset($payload['payload']['application_id']);

            $appKeyPosition =array_search('application_id', $payload['contains']);
            unset($payload['contains'][$appKeyPosition]);

            if(empty($payload['payload']) === true)
            {
                unset($payload['payload']);
            }
        }

        return $applicationId;
    }

    /**
     * Calls rzp.stork.webhook.v1.WebhookAPI/ReplayWebhookByEventId endpoint of stork service.
     * Also see processEventSafe().
     *
     * @param  Event\Entity $event
     * @return void
     * @throws \RZP\Exception\ServerErrorException
     * @throws \Throwable
     */
    public function replayEventByIds(array $input): array
    {
        $response = $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEventByEventIds',
            $input,
            self::REPLAY_EVENT_REQUEST_TIMEOUT_MS
        );
        return json_decode($response->body, true) ?:[];
    }

    /**
     * @param  string $merchantId
     * @return void
     */
    public function invalidateAffectedOwnersCache(string $merchantId)
    {
        $this->trace->info(
            TraceCode::STORK_INVALIDATE_AFFECTED_OWNERS_CACHE_REQ,
            ['merchant_id' => $merchantId, 'mode' => $this->mode]
        );

        $snsMsgPayload = [
            'invalidate_affected_owners_cache_request' => [
                'service'    => $this->service->service,
                'owner_id'   => $merchantId,
                'owner_type' => E::MERCHANT,
            ],
        ];

        try
        {
            $this->service->publishOnSns($snsMsgPayload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::STORK_INVALIDATE_CACHE_FAILED);
        }
    }

    public function deleteWebhooksByOwnerId(string $ownerId)
    {
        $webhooks = $this->list($ownerId)['items'];

        while (count($webhooks) > 0)
        {
            $this->trace->info(
                TraceCode::STORK_INVALIDATE_AFFECTED_OWNERS_CACHE_REQ,
                ['application_id' => $ownerId, 'webhooks' => $webhooks]
            );

            foreach ($webhooks as $webhook)
            {
                $this->delete($webhook['id'], $ownerId);
            }
            $webhooks = $this->list($ownerId)['items'];
        }
    }
}
