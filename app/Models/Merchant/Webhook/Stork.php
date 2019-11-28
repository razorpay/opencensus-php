<?php

namespace RZP\Models\Merchant\Webhook;


use Carbon\Carbon;

use RZP\Models\Event;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Constants\Timezone;

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

    public function __construct()
    {
        $this->service = new \RZP\Services\Stork;
    }

    public function create(Entity $webhook)
    {
        // Todo: Add Base\Entity::getMode() method. getConnectionName() may
        // return slave-live or slave-test. But in create and update it will not
        // because writes do not go to master.
        $this->service->init($webhook->getConnectionName());

        $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            [
                'webhook' => $this->serializeWebhook($webhook),
            ]);
    }

    public function update(Entity $webhook)
    {
        $this->service->init($webhook->getConnectionName());

        $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update',
            [
                'webhook' => $this->serializeWebhook($webhook),
            ]);
    }

    public function upsert(Entity $webhook)
    {
        // Stork's update rpc itself handles upsert behavior.
        return $this->update($webhook);
    }

    public function processEvent(Event\Entity $event, string $mode)
    {
        $this->service->init($mode);

        $merchant = $event->merchant;

        $payload = json_encode($event->toArrayPublic());

        if (empty($merchant) === false)
        {
            $response = (new Merchant\Core)->translateWebhookPayloadIfApplicable($merchant, $payload);

            $payload  = $response['body'];
        }

        $this->service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent',
            [
               'event' => [
                    'service'    => $this->service->service,
                    'owner_id'   => $event->getMerchantId(),
                    'owner_type' => E::MERCHANT,
                    'context'    => '{}',
                    'name'       => $event->event,
                    'payload'    => $payload,
                ],
            ]);
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
                app()->trace->traceException($e);
            }
        }
    }

    public function invalidateCache(string $merchantId, string $mode)
    {
        $this->service->init($mode);

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
            'created_at'    => Carbon::createFromTimestamp($webhook->getCreatedAt(), Timezone::IST)->toIso8601ZuluString(),
            'service'       => $this->service->service,
            'owner_id'      => $webhook->getEntityId() ?: $webhook->getMerchantId(),
            'owner_type'    => $webhook->getEntityType() ?: E::MERCHANT,
            'context'       => '{}',
            'disabled'      => $webhook->isActive() === false,
            'url'           => $webhook->getUrl(),
            'secret'        => $webhook->getSecret(),
            'subscriptions' => array_map(
                function($v) { return ['eventmeta' => ['name' => $v]]; },
                array_keys(array_filter($webhook->getEvents()))),
        ];
    }
}
