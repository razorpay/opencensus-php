<?php

namespace RZP\Models\Merchant\WebhookV2;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

/**
* API working as a proxy layer. Forwards request to stork with minimum
* logic. API accepts webhook input in stork format and populates
* only some implicit fields fields.
*/
class Service extends Base\Service
{
    const ID            = 'id';
    const ACTIVE        = 'active';
    const EVENTS        = 'events';
    const WEBHOOK       = 'webhook';
    const DISABLED      = 'disabled';
    const MERCHANT      = 'merchant';
    const OWNER_ID      = 'owner_id';
    const OWNER_TYPE    = 'owner_type';
    const APPLICATION   = 'application';
    const CREATED_AT    = 'created_at';
    const SUBSCRIPTIONS = 'subscriptions';

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * Type of product from which request is coming - banking, primary
     */
    protected $product;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->product = $this->auth->getRequestOriginProduct();
    }

    /**
     * This method handles the oauth app's create webhook
     * use case and just adds a few implict fields to the input.
     * It then calls a common method to create webhook.
     * @param   array  $input
     * @param   string $appId The app id of the oauth application
     * @return  array
     */
    public function createForOAuthApp(array $input, string $appId): array
    {
        $input = $this->apiToStorkFormat($input);

        $this->validator->validateStorkWebhookInput($input, $this->merchant);
        $this->validator->validatePartnerWithWebhooksAccess($this->merchant);

        $input[self::OWNER_ID]   = $appId;
        $input[self::OWNER_TYPE] = self::APPLICATION;

        return $this->create($input);
    }

    /**
     * This method handles the merchant's create webhook
     * use case and just adds a few implict fields to the input.
     * It then calls a common method to create webhook.
     * @param   array $input
     * @return  array
     */
    public function createForMerchant(array $input): array
    {
        $input = $this->apiToStorkFormat($input);

        $this->validator->validateStorkWebhookInput($input, $this->merchant);

        $input[self::OWNER_ID]   = $this->merchant->getId();
        $input[self::OWNER_TYPE] = self::MERCHANT;

        return $this->create($input);
    }

    /**
     * Creates a webhook on Stork. Temporarily dual writes to API DB.
     * These writes to API DB will be removed later.
     * @param  array  $input - input in stork webhook create format
     * @return array         - stork webhook create response body
     */
    protected function create(array $input): array
    {
        if ($this->auth->isProductBanking() === true)
        {
            $this->checkAndFailIfWebhookExistsOnStork($input);
        }

        $res = (new Stork($this->product))->create($input);

        //TODO: this will be removed once it's all stork
        if ((isset($res[self::ID]) === true) and
            ($this->auth->isProductBanking() === false))
        {
            (new Merchant\Webhook\Core)->createWebhookForStork($input, $this->merchant, $res[self::ID], $res[self::CREATED_AT] ?? 0);
        }

        return $this->storkToApiFormat($res);
    }

    /**
     * Edits the webhook on stork and temporarily edits the webhook
     * entity on API as well. This will be removed in sometime.
     * @param  string $webhookId
     * @param  array  $input     - input in stork format
     * @return array             - stork's webhook edit response body
     */
    public function update(string $webhookId, array $input): array
    {
        $input = $this->apiToStorkFormat($input);

        if ($this->auth->isProductBanking() === true)
        {
            $this->checkAndFailIfWebhookNotExistsOnStork($webhookId);
        }

        $this->validator->validateStorkWebhookInput($input, $this->merchant);

        $input[self::ID]         = $webhookId;
        $input[self::OWNER_ID]   = $this->merchant->getId();
        $input[self::OWNER_TYPE] = self::MERCHANT;

        $res = (new Stork($this->product))->edit($input);

        //TODO: this will be removed once it's all stork
        if ((isset($res[self::ID]) === true) and
            ($this->auth->isProductBanking() === false))
        {
            (new Merchant\Webhook\Core)->updateWebhookForStork($input, $this->merchant, $res[self::ID], $res[self::CREATED_AT] ?? 0);
        }

        return $this->storkToApiFormat($res);
    }

    public function get(string $webhookId): array
    {
        if (($this->app['basicauth']->isHosted() === true) or
            ($this->app['basicauth']->isExpress() === true))
        {
            $res = (new Stork($this->product))->getWithSecret($webhookId, $this->merchant->getId());
        }
        else
        {
            $res = (new Stork($this->product))->get($webhookId, $this->merchant->getId());
        }

        return $this->storkToApiFormat($res);
    }

    public function list(array $params): array
    {
        if (($this->app['basicauth']->isHosted() === true) or
            ($this->app['basicauth']->isExpress() === true))
        {
            $res = (new Stork($this->product))->listWithSecret($this->merchant->getId(), $params);
        }
        else
        {
            $res = (new Stork($this->product))->list($this->merchant->getId(), $params);
        }

        $res['items'] = array_map(function ($v) { return $this->storkToApiFormat($v); }, $res['items']);

        return $res;
    }

    //if webhook already exists on stork throw exception
    protected function checkAndFailIfWebhookExistsOnStork(array $input)
    {
        $webhookCollection = $this->list(['offset' => 0, 'limit' => 2]);
        if ($webhookCollection['count'] > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_STORK_WEBHOOK_ALREADY_CREATED);
        }
    }

    protected function checkAndFailIfWebhookNotExistsOnStork(string $webhookId)
    {
        $webhook = $this->get($webhookId);
        if (isset($webhook[self::ID]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_STORK_WEBHOOK_NOT_FOUND);
        }
    }

    /**
     * events in api format is subscriptions in stork format
     * active field in api format is disabled field in stork format
     *
     * @param array $apiWk webhook in api's format
     * @return array         webhook in stork's format
     */
    protected function apiToStorkFormat(array $apiWk): array
    {
        if (isset($apiWk[self::EVENTS]) === false)
        {
            // setting as empty array to avoid null exceptions
            $apiWk[self::EVENTS] = [];
        }

        $storkWk = $apiWk;

        $storkWk[self::SUBSCRIPTIONS] = array_map(
            function($e)
            {
                return ['eventmeta' => ['name' => $e]];
            },
            array_keys(array_filter($apiWk[self::EVENTS])));

        if (isset($apiWk[self::ACTIVE]) === true)
        {
            $storkWk[self::DISABLED] = !$apiWk[self::ACTIVE];
        }

        unset($storkWk[self::ACTIVE]);
        unset($storkWk[self::EVENTS]);
        return $storkWk;
    }

    /**
     * events in api format is subscriptions in stork format
     * active field in api format is disabled field in stork format
     *
     * @param array $storkWk this is the webhook in stork's format
     * @return array         webhook in APIs format (diff is in events)
     */
    protected function storkToApiFormat(array $storkWk): array
    {
        if (isset($storkWk[self::SUBSCRIPTIONS]) === false)
        {
            // setting as empty array to avoid null exceptions.
            $storkWk[self::SUBSCRIPTIONS] = [];
        }

        $events = [];

        $apiWk = $storkWk;

        $applicableEvents = array_keys(Merchant\Webhook\Event::filterForPublicApi($this->merchant));
        foreach ($applicableEvents as $ename)
        {
            $events[$ename] = false;
        }


        //for all events which are enabled, set the event to 1
        foreach ($storkWk[self::SUBSCRIPTIONS] as $value)
        {
            $events[$value['eventmeta']['name']] = true;
        }

        if (isset($storkWk[self::DISABLED]) === true)
        {
            $apiWk[self::ACTIVE] = !$storkWk[self::DISABLED];
        }
        else
        {
            // DISABLED field is not there if it's false.
            $apiWk[self::ACTIVE] = true;
        }

        unset($apiWk[self::DISABLED]);
        unset($apiWk[self::SUBSCRIPTIONS]);
        $apiWk[self::EVENTS] = $events;

        return $apiWk;
    }
}
