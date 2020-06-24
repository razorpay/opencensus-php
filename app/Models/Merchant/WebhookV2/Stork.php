<?php

namespace RZP\Models\Merchant\WebhookV2;

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
    /**
     * @var \RZP\Services\Stork
     */
    protected $service;

    const WK_GET_ROUTE                = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get';
    const WK_LIST_ROUTE               = '/twirp/rzp.stork.webhook.v1.WebhookAPI/List';
    const WK_DELETE_ROUTE             = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Delete';
    const WK_EDIT_ROUTE               = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update';
    const WK_CREATE_ROUTE             = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create';
    const WK_GET_WITH_SECRET_ROUTE    = '/twirp/rzp.stork.webhook.v1.WebhookAPI/GetWithSecret';
    const WK_LIST_WITH_SECRET_ROUTE   = '/twirp/rzp.stork.webhook.v1.WebhookAPI/ListWithSecret';

    public function __construct(string $product)
    {
        $this->service = app('stork_service');
        $this->service->init(app('rzp.mode'), $product);
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
}
