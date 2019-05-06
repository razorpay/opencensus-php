<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Jobs;
use RZP\Models;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Webhook;

class Core extends Base\Core
{
    public function createWebhook(Merchant\Entity $merchant, array $input)
    {
        $entityId = isset($input[Entity::ENTITY_ID]) ? $input[Entity::ENTITY_ID] : null;

        if ((isset($input[Entity::ENTITY_TYPE]) === true) and  ($input[Entity::ENTITY_TYPE] === Entity::APPLICATION))
        {
            (new Validator)->validatePartnerWithWebhooksAccess($merchant);
        }

        $webhooks = $this->getWebhooksWithEntityId($merchant, $entityId);

        if ($webhooks->count() !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Webhook already created.');
        }

        $webhook = new Entity;

        // Association must happen before build() because the same is used in validations.
        $webhook->merchant()->associate($merchant);

        $webhook->build($input);

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
        return array_keys(Event::filterForPublicApi($merchant));
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
        array $payload,
        Merchant\Entity $merchant,
        String $event,
        Webhook\Entity $webhook) : array
    {
        $signedAccountId = Merchant\Account\Entity::getSignedId($merchant->getId());

        $attributes = [
            Models\Event\Entity::EVENT      => $event,
            Models\Event\Entity::ACCOUNT_ID => $signedAccountId,
            Models\Event\Entity::CONTAINS   => array_keys($payload),
            Models\Event\Entity::CREATED_AT => Carbon::now()->getTimestamp(),
        ];

        $event = new Models\Event\Entity($attributes);

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);

        $data = [
            'mode'       => $this->app['rzp.mode'],
            'event'      => json_encode($event->toArrayPublic()),
            'event_name' => $event->event,
            'webhook_id' => $webhook->getId(),
            // Refer Inferno's eventQueuedAt.
            'queued_at'  => millitime(),
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
