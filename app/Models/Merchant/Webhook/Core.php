<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Jobs;
use RZP\Models;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Webhook;

class Core extends Base\Core
{
    public function createWebhook(Merchant\Entity $merchant, array $input)
    {
        $entityId = isset($input[Entity::ENTITY_ID]) ? $input[Entity::ENTITY_ID] : null;

        $webhooks = $this->getWebhooksWithEntityId($merchant, $entityId);

        if ($webhooks->count() !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Webhook already created.');
        }

        $webhook = (new Webhook\Entity)->build($input);

        $webhook->merchant()->associate($merchant);

        $this->repo->saveOrFail($webhook);

        return $webhook;
    }

    public function editWebhook(Merchant\Entity $merchant, string $webhookId, array $input)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchant($webhookId, $merchant);

        $webhook->edit($input);

        $this->repo->saveOrFail($webhook);

        return $webhook;
    }

    public function fetchApplicableWebhookEvents(Merchant\Entity $merchant)
    {
        return array_keys(Event::filterByFeatures(
                                    array_flip(Event::getLaunchedEventNames()),
                                    $merchant->getEnabledFeatures()));
    }

    public function getWebhooks(Merchant\Entity $merchant)
    {
        return $this->repo->webhook->fetch([], $merchant->getId());
    }

    public function getWebhooksWithEntityId(Merchant\Entity $merchant, string $entityId = null)
    {
        return $this->repo->webhook->findMultipleByMerchantAndEntityId($merchant, $entityId);
    }

    public function prepareAndDispatchWebhook(
        Merchant\Entity $merchant,
        String $event,
        array $input,
        Webhook\Entity $webhook)
    {
        $payload = $input['payload'];

        $data = $this->prepareData($payload, $merchant, $event, $webhook);

        $this->dispatchWebhook($data,$event);
    }

    protected function prepareData(
        Array $payload,
        Merchant\Entity $merchant,
        String $event,
        Webhook\Entity $webhook) : Array
    {
        $attributes = [
            Models\Event\Entity::EVENT      => $event,
            Models\Event\Entity::ACCOUNT_ID => $merchant->getId(),
            Models\Event\Entity::CONTAINS   => array_keys($payload),
        ];
        $event = new Models\Event\Entity($attributes);

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);

        $data = [
            'mode'       => $this->app['rzp.mode'],
            'event'      => json_encode($event->toArrayPublic()),
            'webhook_id' => $webhook->getId()
        ];

        return $data;
    }

    protected function dispatchWebhook(array $data, String $event)
    {
        Jobs\WebHook::dispatch($data)->using([$event]);
    }

    /*
    * Check if the merchant has an active webhook for the event
    */
    public function isWebhookActiveAndEnabled(Webhook\Entity $webhook, String $event): bool
    {
        return (($webhook !== null) and
            ($webhook->isActive() === true) and
            ($webhook->isEventEnabled($event)));
    }
}
