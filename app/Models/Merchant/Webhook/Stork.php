<?php

namespace RZP\Models\Merchant\Webhook;


use Request;
use Throwable;
use Carbon\Carbon;
use Requests_Session;
use Requests_Response;

use RZP\Models\Event;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Constants\Timezone;
use RZP\Exception\ServerErrorException;

/**
 * Class Stork
 *
 * Holds implementation for various communication to stork service
 * against webhook module of api. E.g. dual writing webhook etc. And later more.
 *
 * @package RZP\Models\Merchant\Webhook
 */
class Stork
{
    /**
     * If actual http requests should be made.
     * Dual write is mocked in unit tests.
     * @var boolean
     */
    protected $mock;

    /**
     * Name of owning service for requests to stork.
     * @var string
     */
    protected $service;

    /**
     * @var Requests_Session
     */
    protected $request;

    public function init(string $mode)
    {
        $config = config('stork');

        $this->mock = $config['mock'];
        // Service name is same as authenticated user.
        $this->service = $config['service_prefix'] . $config['auth'][$mode]['user'];
        $this->request = new Requests_Session(
            $config['url'],
            // Common headers for requests.
            [
                'X-Request-ID' => Request::getTaskId(),
                'Content-Type' => 'application/json',
            ],
            [],
            // Options and authentication for requests.
            [
                'timeout' => 1, // Minimum possible value is 1 second.
                'auth' => [$config['auth'][$mode]['user'], $config['auth'][$mode]['pass']],
            ]);
    }

    public function create(Entity $webhook)
    {
        // Todo: Add Base\Entity::getMode() method. getConnectionName() may
        // return slave-live or slave-test. But in create and update it will not
        // because writes do not go to master.
        $this->init($webhook->getConnectionName());

        $this->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create',
            [
                'webhook' => $this->serializeWebhook($webhook),
            ]);
    }

    public function update(Entity $webhook)
    {
        $this->init($webhook->getConnectionName());

        $this->request(
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
        $this->init($mode);

        $this->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent',
            [
               'event' => [
                    'service'    => $this->service,
                    'owner_id'   => $event->getMerchantId(),
                    'owner_type' => E::MERCHANT,
                    'context'    => '{}',
                    'name'       => $event->event,
                    'payload'    => json_encode($event->toArrayPublic()),
                ],
            ]);
    }

    public function invalidateCache(string $merchantId, string $mode)
    {
        $this->init($mode);

        $this->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/InvalidateCache',
            [
                'service'    => $this->service,
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
            'service'       => $this->service,
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

    protected function request(string $path, array $payload): Requests_Response
    {
        // Just for tests!
        if ($this->mock === true)
        {
            return new Requests_Response;
        }

        $res = null;
        $exception = null;

        try
        {
            $res = $this->request->post($path, [], json_encode($payload));
        }
        catch (Throwable $e)
        {
            $exception = $e;
        }

        if (($exception !== null) or ($res->success !== true))
        {
            throw new ServerErrorException(
                "Failed to complete request",
                ErrorCode::SERVER_ERROR_STORK_FAILURE,
                ['req_path' => $path] + ($res ? ['resp_status_code' => $res->status_code, 'resp_body' => $res->body] : []),
                $exception);
        }

        return $res;
    }
}
