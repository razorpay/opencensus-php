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
    const ID                = 'id';
    const ACTIVE            = 'active';
    const EVENTS            = 'events';
    const WEBHOOK           = 'webhook';
    const WEBHOOK_ID        = 'webhook_id';
    const CONTEXT           = 'context';
    const SERVICE           = 'service';
    const DISABLED          = 'disabled';
    const MERCHANT          = 'merchant';
    const OWNER_ID          = 'owner_id';
    const CREATED_BY        = 'created_by';
    const UPDATED_BY        = 'updated_by';
    const CREATED_AT        = 'created_at';
    const OWNER_TYPE        = 'owner_type';
    const APPLICATION       = 'application';
    const SUBSCRIPTIONS     = 'subscriptions';
    const CREATED_BY_EMAIL  = 'created_by_email';
    const UPDATED_BY_EMAIL  = 'updated_by_email';

    /**
     * minor optimization to avoid an extra call to db. Good to have under assumption
     * that created_by & updated_by fields will be same in majority of situations
     *
     * @var array
     */
    var $userIdToEmail = [];

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

        $this->unsetImplicitFields($input);

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

        $this->unsetImplicitFields($input);

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

        $this->setUserIdForInputAndKey($input, self::CREATED_BY);

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

        $this->unsetImplicitFields($input);

        if ($this->auth->isProductBanking() === true)
        {
            $this->checkAndFailIfWebhookNotExistsOnStork($webhookId);
        }

        $this->validator->validateStorkWebhookInput($input, $this->merchant);

        $input[self::ID]         = $webhookId;
        $input[self::OWNER_ID]   = $this->merchant->getId();
        $input[self::OWNER_TYPE] = self::MERCHANT;

        $this->setUserIdForInputAndKey($input, self::UPDATED_BY);

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

    /**
     * deletes a webhook having id = $webhookId
     * @param string $webhookId
     */
    public function delete(string $webhookId)
    {
        (new Stork($this->product))->delete($webhookId, $this->merchant->getId());

        // since product banking webhooks are only present on stork.
        if ($this->auth->isProductBanking() === false)
        {
            try
            {
                // soft deleting the webhook enity in API.
                $webhook = $this->repo->webhook->findByIdAndMerchant($webhookId, $this->merchant);
                $this->repo->deleteOrFail($webhook);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::CRITICAL,
                    TraceCode::DELETE_WEBHOOK_FOR_STORK_FAILED,
                    [
                        'stork_wk_id' => $webhookId,
                    ]);
            }
        }
    }

    /**
     * Fetches webhook delivery metrics from Stork for a webhookId & merchant with the input filters.
     *
     * @param string $id
     * @param array $input
     * @return array
     */
    public function getAnalytics(string $id, array $input)
    {
        $input[self::WEBHOOK_ID] = $id;
        $input[self::OWNER_ID] = $this->merchant->getId();
        return (new Stork($this->product))->getAnalytics($input);
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

        if (isset($storkWk[self::CREATED_BY]) === true)
        {
            $storkWk[self::CREATED_BY_EMAIL] = $this->fetchEmailFromUserId($storkWk[self::CREATED_BY]);
        }

        if (isset($storkWk[self::UPDATED_BY]) === true)
        {
            $storkWk[self::UPDATED_BY_EMAIL] = $this->fetchEmailFromUserId($storkWk[self::UPDATED_BY]);
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

    /**
     * This method unsets the fields for the given input.
     * Safe fields are fields which should be inferred from the context of the
     * request (user, auth). Operation should not depend on the values of these fields
     * provided from the frontend. Wherever applicable, the flow which is using
     * this method should set the value for these implicit fields on its own.
     * Not failing the validations here because the FE tends to send the whole model
     * during updation and they should not be expected to unset these fields before
     * sending. Backend should control these things.
     * @param array &$input reference to the user input
     */
    protected function unsetImplicitFields(array &$input)
    {
        unset($input[self::SERVICE]);
        unset($input[self::OWNER_ID]);
        unset($input[self::OWNER_TYPE]);
        unset($input[self::CONTEXT]);
        unset($input[self::CREATED_BY]);
        unset($input[self::UPDATED_BY]);
        unset($input[self::CREATED_BY_EMAIL]);
        unset($input[self::UPDATED_BY_EMAIL]);
    }

    /**
     * checks if user id is present in auth. If it is present
     * it sets the user id aginst the key - $keyForId in the input.
     * @param  array  &$input   reference to the input in which user id field needs to be set
     * @param  string $keyForId key against which user id needs to be set in the input array passed
     */
    protected function setUserIdForInputAndKey(array &$input, string $keyForId)
    {
        $userId = is_null($this->user) === true ? '' : $this->user->getUserId();
        if (empty($userId) === false)
        {
            $input[$keyForId] = $userId;
        }
    }

    /**
     * fetches the user email from the given user id.
     * @param  string $userId userId of the user
     * @return string         email id for the given user id
     */
    protected function fetchEmailFromUserId(string $userId): string
    {
        if (empty($userId) === true)
        {
            return '';
        }

        // minor optimization
        if (isset($userIdToEmail[$userId]) === true)
        {
            return $userIdToEmail[$userId];
        }

        $user                   = $this->repo->user->find($userId, ['email']);
        $userIdToEmail[$userId] = is_null($user) ? '' : $user->email;

        return $userIdToEmail[$userId];
    }
}
