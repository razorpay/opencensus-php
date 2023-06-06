<?php

namespace RZP\Models\Merchant\WebhookV2;

use Mail;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Event;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Admin\ConfigKey;
use RZP\Modules\Migrate\Migrate;
use RZP\Models\Event\Entity as EventEntity;
use RZP\Models\Admin\Service as AdminService;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Account\Entity as AccountEntity;

/**
* API working as a proxy layer. Forwards request to stork with minimum
* logic. API accepts webhook input in stork format and populates
* only some implicit fields fields.
*/
class Service extends Base\Service
{
    const ID                = 'id';
    const ENTITY            = 'entity';
    const ACTIVE            = 'active';
    const EVENTS            = 'events';
    const WEBHOOK           = 'webhook';
    const WEBHOOK_ID        = 'webhook_id';
    const CONTEXT           = 'context';
    const SERVICE           = 'service';
    const DISABLED          = 'disabled';
    const MERCHANT          = 'merchant';
    const OWNER_ID          = 'owner_id';
    const EMAIL_TYPE        = 'type';
    const CREATED_BY        = 'created_by';
    const UPDATED_BY        = 'updated_by';
    const CREATED_AT        = 'created_at';
    const OWNER_TYPE        = 'owner_type';
    const ALERT_EMAIL       = 'alert_email';
    const APPLICATION       = 'application';
    const SUBSCRIPTIONS     = 'subscriptions';
    const APPLICATION_ID    = 'application_id';
    const CREATED_BY_EMAIL  = 'created_by_email';
    const UPDATED_BY_EMAIL  = 'updated_by_email';
    const EZETAP_MERCHANT   = 'ezetap';

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
        $this->traceOperationEntry('create_for_oauth', ['app_id' => $appId ?? '']);

        $input = $this->apiToStorkFormat($input);

        $this->unsetImplicitFields($input);

        $this->validator->validateStorkWebhookInput($input, $this->merchant);
        $this->validator->validatePartnerWithWebhooksAccess($this->merchant);
        $this->validator->validatePartnerMerchantHasApplicationAccess($this->merchant, $appId);

        $input[self::OWNER_ID]   = $appId;
        $input[self::OWNER_TYPE] = self::APPLICATION;

        $this->traceOperationExit('create_for_oauth', ['app_id' => $appId ?? '']);

        return $this->create($input);
    }

    /**
     * This method handles the merchant's create webhook
     * use case and just adds a few implicit fields to the input.
     * It then calls a common method to create webhook.
     * @param array $input
     * @param string|null $merchantId
     * @return  array
     * @throws Exception\BadRequestException
     */
    public function createForMerchant(array $input, string $merchantId = null): array
    {
        $merchantId = $merchantId ?? $this->merchant->getId();

        $this->traceOperationEntry('create_for_merchant', [AccountEntity::MERCHANT_ID => $merchantId]);

        $this->blockWebhookCreationForMFN($this->merchant);

        $input = $this->apiToStorkFormat($input);

        $this->unsetImplicitFields($input);

        $this->validator->validateStorkWebhookInput($input, $this->merchant);

        $input[self::OWNER_ID]   = $merchantId;
        $input[self::OWNER_TYPE] = self::MERCHANT;

        $this->traceOperationExit('create_for_merchant', [AccountEntity::MERCHANT_ID => $merchantId]);

        $response = $this->create($input);

        $this->sendSelfServeSuccessAnalyticsEventToSegmentForMerchantCreatedWebhook();

        return $response;
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

        $res = (new Stork($this->mode, $this->product))->create($input);

        $merchantId = ($input[self::OWNER_TYPE] === self::MERCHANT) ? $input[self::OWNER_ID] : $this->merchant->getId();

        $this->traceStorkOperationSuccess('create', ['webhook_id' => $res[self::ID] ?? '', 'webhook' => $this->getStorkWebhookForTracing($res),
                                                              AccountEntity::MERCHANT_ID => $merchantId]);

        return $this->storkToApiFormat($res);
    }

    /**
     * Edits the webhook on stork and temporarily edits the webhook
     * entity on API as well. This will be removed in sometime.
     * @param string $webhookId
     * @param array $input - input in stork format
     * @param string|null $merchantId
     * @return array             - stork's webhook edit response body
     * @throws Exception\BadRequestException
     */
    public function update(string $webhookId, array $input, string $merchantId = null): array
    {
        $merchantId = $merchantId ?? $this->merchant->getId();

        $this->traceOperationEntry('update', ['webhook_id' => $webhookId ?? '', AccountEntity::MERCHANT_ID => $merchantId]);

        $this->blockWebhookCreationForMFN($this->merchant);

        $input = $this->apiToStorkFormat($input);

        $this->unsetImplicitFields($input);

        $this->validator->validateStorkWebhookInput($input, $this->merchant);

        $isOauthApplicationWebhook = isset($input[self::APPLICATION_ID]);
        $isOauthApplicationWebhook === true ? $this->validator->validatePartnerWithWebhooksAccess($this->merchant) : null;
        $isOauthApplicationWebhook === true ? $this->validator->validatePartnerMerchantHasApplicationAccess($this->merchant, $input[self::APPLICATION_ID])  : null;

        $input[self::ID]           = $webhookId;
        $input[self::OWNER_ID]     = $isOauthApplicationWebhook === true ? $input[self::APPLICATION_ID] : $merchantId;
        $input[self::OWNER_TYPE]   = $isOauthApplicationWebhook === true ? self::APPLICATION : self::MERCHANT;

        $this->setUserIdForInputAndKey($input, self::UPDATED_BY);

        $res = (new Stork($this->mode, $this->product))->edit($input);

        $this->traceStorkOperationSuccess('update', ['webhook_id' => $res[self::ID] ?? '', 'webhook' => $this->getStorkWebhookForTracing($res),
                                                              AccountEntity::MERCHANT_ID => $merchantId]);

        $this->traceOperationExit('update', ['webhook_id' => $res[self::ID] ?? '', AccountEntity::MERCHANT_ID => $merchantId]);

        return $this->storkToApiFormat($res);
    }

    public function get(string $webhookId, string $merchantId = null): array
    {
        $merchantId = $merchantId ?? $this->merchant->getId();

        $this->traceOperationEntry('get', ['webhook_id' => $webhookId ?? '', AccountEntity::MERCHANT_ID => $merchantId]);

        if (($this->app['basicauth']->isHosted() === true) or
            ($this->app['basicauth']->isExpress() === true))
        {
            $res = (new Stork($this->mode, $this->product))->getWithSecret($webhookId, $merchantId);
        }
        else
        {
            $res = (new Stork($this->mode, $this->product))->get($webhookId, $merchantId);
        }

        $this->traceOperationExit('get', ['webhook_id' => $webhookId ?? '', AccountEntity::MERCHANT_ID => $merchantId]);

        return $this->storkToApiFormat($res);
    }

    public function list(array $params, string $merchantId = null): array
    {
        $this->traceOperationEntry('list');

        $ownerId = $merchantId ?? $this->merchant->getId();

        if (($this->app['basicauth']->isHosted() === true) or
            ($this->app['basicauth']->isExpress() === true))
        {
            $res = (new Stork($this->mode, $this->product))->listWithSecret($ownerId, $params);
        }
        else
        {
            $isListWkRequestForOauthApplication = isset($params[self::APPLICATION_ID]);
            if ($isListWkRequestForOauthApplication === true)
            {
                $this->validator->validatePartnerMerchantHasApplicationAccess($this->merchant, $params[self::APPLICATION_ID]);
                $ownerId = $params[self::APPLICATION_ID];
            }

            $res = (new Stork($this->mode, $this->product))->list($ownerId, $params);
        }

        $res['items'] = array_map(function ($v) { return $this->storkToApiFormat($v); }, $res['items']);

        $res['items'] = array_filter($res['items'], function ($webhook)
            {
                return ($webhook[self::OWNER_TYPE] !== self::EZETAP_MERCHANT);
            });
        $res['count'] = count($res['items']);

        $this->traceOperationExit('list', [AccountEntity::MERCHANT_ID => $ownerId]);

        // Hack: When toArrayHosted happens on a collection, it just returns
        // array of entities e.g. [{}, {}]. It is not consistent with toArrayPublic
        // where it returns same wrapped in collection entity
        // e.g. ["entity": "collection", "count": 2, "items": {}, {}].

        if (($this->app['basicauth']->isHosted() === true) or
            ($this->app['basicauth']->isExpress() === true))
        {
            return $res['items'];
        }

        return $res;
    }

    /**
     * deletes a webhook having id = $webhookId
     * @param string $webhookId
     * @param string|null $merchantId
     * @throws Exception\BadRequestException
     */
    public function delete(string $webhookId, string $merchantId = null)
    {
        if ($this->auth->isProductBanking() === true)
        {
            /*
            * Delete webhook is used in PG product only.
            * Throw exception if banking product tries to hit
            * Fix for https://razorpay.atlassian.net/browse/SBB-945
            */
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }
        $merchantId = $merchantId ?? $this->merchant->getId();

        $this->traceOperationEntry('delete', ['webhook_id' => $webhookId ?? '', AccountEntity::MERCHANT_ID => $merchantId]);

        (new Stork($this->mode, $this->product))->delete($webhookId, $merchantId);

        $this->traceStorkOperationSuccess('delete', ['webhook_id' => $webhookId ?? '', AccountEntity::MERCHANT_ID => $merchantId]);

        $this->traceOperationExit('delete', ['webhook_id' => $webhookId ?? '', AccountEntity::MERCHANT_ID => $merchantId]);
    }

    /**
     ** @param array|null $input
     * @param string $accountId
     *
     * @return array
     * @throws Exception\BadRequestException|Exception\BadRequestValidationFailureException
     */
    public function createOnboardingWk(array $input, string $accountId)
    {
        $this->setPartnerContext();

        $this->validator->validateOnboardingWkAction($accountId, $this->merchant);

        $this->validator->validateOnboardingWkInput($input);

        $this->convertEventsToAssocArray($input);

        $res = $this->createForMerchant($input, $accountId);

        $publicResponse = $this->modifyResponseForOnboardingWk($res);

        $dimensions = $this->getDimensionsForWebhookData();

        $this->trace->count(Metric::ACCOUNT_V2_WEBHOOK_CREATE_SUCCESS_TOTAL, $dimensions);

        return $publicResponse;
    }

    /**
     * @param string $webhookId
     * @param string $accountId
     *
     * @return array
     * @throws Exception\BadRequestException|Exception\BadRequestValidationFailureException
     */
    public function fetchOnboardingWk(string $webhookId, string $accountId)
    {
        $timeStarted = microtime(true);

        $this->setPartnerContext();

        $this->validator->validateOnboardingWkAction($accountId, $this->merchant);

        $res = $this->get($webhookId, $accountId);

        $publicResponse = $this->modifyResponseForOnboardingWk($res);

        $dimensions = $this->getDimensionsForWebhookData();

        $this->trace->count(Metric::ACCOUNT_V2_WEBHOOK_FETCH_SUCCESS_TOTAL, $dimensions);

        $this->trace->histogram(Metric::ACCOUNT_V2_WEBHOOK_FETCH_TIME_MS, get_diff_in_millisecond($timeStarted), $dimensions);

        return $publicResponse;

    }

    private function setPartnerContext()
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if (empty($partnerMerchantId) === false)
        {
            $this->merchant = (new Merchant\Repository())->findOrFailPublic($partnerMerchantId);
        }

    }

    /**
     * @param array|null $input
     * @param string $accountId
     *
     * @return array
     * @throws Exception\BadRequestException|Exception\BadRequestValidationFailureException
     */
    public function listOnboardingWk(array $input, string $accountId)
    {
        $timeStarted = microtime(true);

        $this->setPartnerContext();

        $this->validator->validateOnboardingWkAction($accountId, $this->merchant);

        $res = $this->list($input, $accountId);

        foreach ($res['items'] as &$item)
        {
            $this->modifyResponseForOnboardingWk($item);
        }

        $dimensions = $this->getDimensionsForWebhookData();

        $this->trace->count(Metric::ACCOUNT_V2_WEBHOOK_FETCH_ALL_SUCCESS_TOTAL, $dimensions);

        $this->trace->histogram(Metric::ACCOUNT_V2_WEBHOOK_FETCH_ALL_TIME_MS, get_diff_in_millisecond($timeStarted), $dimensions);

        return $res;
    }

    /**
     * @param string $webhookId
     * @param array|null $input
     * @param string $accountId
     *
     * @return array
     * @throws Exception\BadRequestException|Exception\BadRequestValidationFailureException
     */
    public function updateOnboardingWk(string $webhookId, array $input, string $accountId)
    {
        $this->setPartnerContext();

        $this->validator->validateOnboardingWkAction($accountId, $this->merchant);

        $this->validator->validateOnboardingWkInput($input);

        $this->convertEventsToAssocArray($input);

        $res = $this->update($webhookId, $input, $accountId);

        $publicResponse = $this->modifyResponseForOnboardingWk($res);

        $dimensions = $this->getDimensionsForWebhookData();

        $this->trace->count(Metric::ACCOUNT_V2_WEBHOOK_UPDATE_SUCCESS_TOTAL, $dimensions);

        return $publicResponse;
    }

    /**
     * @param string $webhookId
     * @param string $accountId
     *
     * @return void
     * @throws Exception\BadRequestException
     */
    public function deleteOnboardingWk(string $webhookId, string $accountId)
    {
        $this->setPartnerContext();

        $this->validator->validateOnboardingWkAction($accountId, $this->merchant);

        $this->delete($webhookId, $accountId);

        $dimensions = $this->getDimensionsForWebhookData();

        $this->trace->count(Metric::ACCOUNT_V2_WEBHOOK_DELETE_SUCCESS_TOTAL, $dimensions);
    }

    protected function convertEventsToAssocArray(array &$apiWk)
    {
        // convert events array to associative (with each event having binary val '1') if passed
        // as a sequential array. This currently happens in case of onboarding webhooks
        if (Arr::isAssoc($apiWk[self::EVENTS]) === false)
        {
            foreach ($apiWk[self::EVENTS] as $key => $val)
            {
                unset($apiWk[self::EVENTS][$key]);

                $apiWk[self::EVENTS][$val] = 1;
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
        return (new Stork($this->mode, $this->product))->getAnalytics($input);
    }

    /**
     * sends email for the given email type and input data
     * @param string $emailType type of email to be sent
     * @param array $input      input containing data to build the mail.
     */
    public function sendEmail(string $emailType, array $input)
    {
        $this->validator->validateSendEmailInput($emailType, $input);
        $fn = 'sendEmailFor' . studly_case($emailType);
        $this->$fn($input);
    }

    //if webhook already exists on stork throw exception
    protected function checkAndFailIfWebhookExistsOnStork(array $input)
    {
        $webhookCollection = $this->list(['offset' => 0, 'limit' => 2]);
        if ($webhookCollection['count'] > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_STORK_WEBHOOK_ALREADY_CREATED,
                null,
                [
                    "webhook_id" => $webhookCollection['items'][0]['id']
                ]
            );
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
     * @return array       webhook in stork's format
     */
    protected function apiToStorkFormat(array $apiWk): array
    {
        if (isset($apiWk[self::EVENTS]) === false)
        {
            // setting as empty array to avoid null exceptions
            $apiWk[self::EVENTS] = [];
        }

        if (isset($apiWk[self::SUBSCRIPTIONS]) === false)
        {
            // setting as empty array to avoid null exceptions
            $apiWk[self::SUBSCRIPTIONS] = [];
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
        $apiWk[self::ENTITY] = 'webhook';

        $applicableEvents = array_keys(Merchant\Webhook\Event::filterForPublicApi($this->merchant));
        foreach ($applicableEvents as $ename)
        {
            $events[$ename] = false;
        }


        //for all events which are enabled, set the event to 1
        foreach ($storkWk[self::SUBSCRIPTIONS] as $value)
        {
            if (array_key_exists($value['eventmeta']['name'], $events) === true)
            {
                $events[$value['eventmeta']['name']] = true;
            }
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

        if ((isset($storkWk[self::OWNER_TYPE]) and
            ($storkWk[self::OWNER_TYPE]) === self::APPLICATION))
        {
            $apiWk[self::APPLICATION_ID] = $storkWk[self::OWNER_ID] ?? '';
        }

        unset($apiWk[self::DISABLED]);
        unset($apiWk[self::SUBSCRIPTIONS]);
        $apiWk[self::EVENTS] = $events;

        return $apiWk;
    }

    /**
     * modify the response for onboarding webhooks by converting
     * the events array from associative to sequential
     *
     * @param array $apiWk
     * @return array
     */
    protected function modifyResponseForOnboardingWk(array &$apiWk): array
    {
        $events = [];

        foreach ($apiWk[self::EVENTS] as $key => $val)
        {
            unset($apiWk[self::EVENTS][$key]);

            if ($val === true)
            {
                array_push($events, $key);
            }
        }

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
        $email                  = is_null($user) ? '' : $user->email;
        $userIdToEmail[$userId] = is_null($email) ? '' : $email;

        return $userIdToEmail[$userId];
    }

    protected function sendEmailForDeactivate(array $data)
    {
        $options = [
            'mode' => $this->mode,
            'type' => 'deactivate',
        ];

        $webhook = $data[self::WEBHOOK];


        $this->trace->info(
            TraceCode::WEBHOOK_DEACTIVATE_EMAIL_FOR_STORK,
            [
                'stork_wk_id' => $webhook[self::ID],
            ]);

        if (isset($webhook[self::ALERT_EMAIL]) === true)
        {
            // if present email sent to this instead of transaction_report_email of the merchant
            $options['recipient_email'] = $webhook[self::ALERT_EMAIL];
        }

        // merchant object is required for transaction_report_email and billing_label.
        $merchantEntity = $this->repo->merchant->findOrFail($webhook[self::OWNER_ID]);

        if ($merchantEntity->isLinkedAccount() === true)
        {
            return;
        }

        $merchantEntity = $merchantEntity->toArrayPublic();

        // $webhook array passed to this should contain 'url' and 'id' field (both are mandatory)
        $webhookMail = new WebhookMail($webhook, $merchantEntity, $options);

        Mail::queue($webhookMail);
    }

    protected function traceOperationEntry(string $operation,  array $extraParams = [])
    {
        $this->traceOperation(TraceCode::WEBHOOK_V2_PATH_OPERATION_ENTRY, $operation, $extraParams);
    }

    protected function traceOperationExit(string $operation, array $extraParams = [])
    {
        $this->traceOperation(TraceCode::WEBHOOK_V2_PATH_OPERATION_EXIT, $operation, $extraParams);
    }

    protected function traceStorkOperationSuccess(string $operation, array $extraParams = [])
    {
        $this->traceOperation(TraceCode::WEBHOOK_V2_PATH_STORK_OPERATION_SUCCESS, $operation, $extraParams);
    }

    protected function traceOperation(string $traceCode, string $operation, array $extraParams = [])
    {
        $params = $this->getBaseParamsForTracing($operation);
        $params = array_merge($params, $extraParams);
        $this->trace->info($traceCode, $params);
    }

    protected function getBaseParamsForTracing(string $operation): array
    {
        return [
            'operation'        => $operation,
            'merchant_id'      => $this->auth->getMerchantId() ?? '',
            'origin_product'   => $this->auth->getRequestOriginProduct(),
        ];
    }

    protected function getStorkWebhookForTracing(array $wk): array
    {
        unset($wk['secret']);
        return $wk;
    }

    /**
     * Reads each line of uploaded csv file expecting json encoded webhook event
     * payload, and sends to stork as a new webhook event. The first line of csv
     * file is ignored assuming header. It runs in sync, max file size expected
     * is 1MB, and max lines in the file must not exceed 1000.
     *
     * Sample csv file looks like:
     * "ownerid","payload"
     * "CBcPtPwFgpjdUp","{""entity"":""event"",""account_id"":""acc_CBcPtPwFgpjdUp"",""event"":""payment.authorized"",""contains"":[""payment""],""payload"":{""payment"":{""entity"":{""id"":""pay_FNSEUwYGlx6uEc"",""entity"":""payment"",""created_at"":1596711949}}},""created_at"":1596711963}"
     *
     * @param  array  $input
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function processWebhookEventsFromCsv(array $input)
    {
        $this->trace->info(TraceCode::PROCESS_WEBHOOK_EVENTS_FROM_CSV_REQUEST);

        $this->validator->validateProcessWebhookEventsFromCsvInput($input);

        $rows = array_map('str_getcsv', file($input[Constant::FILE]));
        // Ignores header line.
        array_shift($rows);

        $merchantIds = array_values(array_unique(array_pluck($rows, 0)));
        $merchantsById = $this->repo->merchant->findManyOrFailPublic($merchantIds)->keyBy(Constant::ID);

        // Validates and builds all event objects in one iteration.
        $events = [];
        foreach ($rows as $row)
        {
            $merchantId = $row[0];
            $eventAttrs = json_decode($row[1], true);
            // Because this attribute is derived and is not expected while building entity.
            unset($eventAttrs['entity']);

            $event = (new Event\Entity)->build($eventAttrs);
            $event->generateId();
            $event->merchant()->associate($merchantsById->get($merchantId));

            $events[] = $event;
        }

        // Dispatches all events to stork.
        $stork = new Stork($this->mode, $this->product);
        foreach ($events as $event)
        {
            $stork->processEventSafe($event);
        }
    }

    /**
     * Expects an array containing event ids as input. It makes sync call to replay the events by id on stork.
     *
     * @param  array  $input
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function processWebhookEventsByIds(array $input): array
    {
        $this->trace->info(TraceCode::PROCESS_WEBHOOK_EVENTS_BY_IDS_REQUEST);

        // Dispatches all events to stork.
        return (new Stork($this->mode, $this->product))->replayEventByIds($input);
    }

    /**
     * See WebhookV2Controller's processWebhook.
     * @param  string $event
     * @param  array  $input
     * @return void
     */
    public function processWebhook(string $event, array $input)
    {
        $merchant = $this->merchant->isLinkedAccount() ? $this->merchant->parent : $this->merchant;
        $payloads = $input['payloads'] ?? [$input['payload']];
        $signedAccountId = Merchant\Account\Entity::getSignedId($this->merchant->getId());

        $stork = new Stork($this->mode);

        foreach ($payloads as $payload)
        {
            $eventAttrs = [
                EventEntity::EVENT      => $event,
                EventEntity::ACCOUNT_ID => $signedAccountId,
                EventEntity::CONTAINS   => array_keys($payload),
                EventEntity::CREATED_AT => Carbon::now()->getTimestamp(),
            ];
            $eventEntity = new EventEntity($eventAttrs);
            $eventEntity->generateId();
            $eventEntity->setPayload($payload);
            $eventEntity->merchant()->associate($this->merchant);

            $stork->processEventSafe($eventEntity);
        }
    }

    /**
     * An array of mids are passed as an input. Stork has a field
     * alert_email for each webhook entity. For each mid, this
     * route will make sure that the alert_email field of
     * the webhook entity in Stork belonging to the mid, will
     * be populated with `transactions_report_email` from the
     * merchants table in the API.
     *
     * sample input :
     * {
     *  source : ['mids' : ['merchant01', merchant02']],
     *  'is_dry_run' : true/false (not mandatory)
     * }
     *
     * @param array $input
     * @return array
     */
    public function webhookEmailStorkRecon(array $input): array
    {
        $this->trace->info(TraceCode::WEBHOOK_EMAIL_STORK_RECON_REQUEST, $input);

        $isDryRun = $input[self::IS_DRY_RUN] ?? false;

        $source  = new AlertEmailRecon\MigrateSource();
        $target  = new AlertEmailRecon\MigrateTarget();
        $migrate = new Migrate($source, $target);

        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, $isDryRun);
    }

    /**
     * @return array
     */
    public function getWebhookEvents(): array
    {
        $webhookEvents = array_keys(Merchant\Webhook\Event::filterForPublicApi($this->merchant));

        $this->removeWebhookEventsFromResponse($webhookEvents);

        return $webhookEvents;
    }

    protected function removeWebhookEventsFromResponse(array & $webhookEvents)
    {
        $webhookEvents = array_values(array_diff($webhookEvents, Merchant\Webhook\Event::getEventsSkippedFromListingApi()));
    }

    private function getDimensionsForWebhookData(): array
    {
        $partner = $this->merchant;

        $dimensions = [
            'partner_type' => $partner->getPartnerType()
        ];

        return $dimensions;
    }

    // setting Webhook URL for all MFN merchants
    public function handleWebhookForMFN(Merchant\Entity $merchantEntity, bool $shouldSync = false)
    {
        $this->merchant = $merchantEntity;

        // the requestOriginProduct will be set back to its initial value once the task is done
        $previousRequestOriginProduct = $this->app['basicauth']->getRequestOriginProduct();

        $this->app['basicauth']->setRequestOriginProduct(Product::BANKING);

        $this->product = Product::BANKING;

        $this->createWebhookForMFN($merchantEntity->getId());

        if ($shouldSync === true)
        {
            // if shouldSync is true we need to add webhooks to both test and live mode
            // the below written map helps in changing the mode from test to live or vice versa.
            $invertedModeMapping = [
                Mode::LIVE => Mode::TEST,
                Mode::TEST => Mode::LIVE
            ];

            $this->mode = $invertedModeMapping[$this->mode];

            $this->createWebhookForMFN($merchantEntity->getId());

            $this->mode = $invertedModeMapping[$this->mode];
        }

        $this->app['basicauth']->setRequestOriginProduct($previousRequestOriginProduct);
    }

    protected function getWebhookUrlForMFN()
    {
        switch ($this->mode)
        {
            case Mode::LIVE:
                $mfnWebhookUrl = (new AdminService)->getConfigKey(['key' => ConfigKey::RX_WEBHOOK_URL_FOR_MFN]);

                if (empty($mfnWebhookUrl) === true)
                {
                    $mfnWebhookUrl = Constant::MFN_DEFAULT_CUSTOM_WEBHOOK_URL;
                }

                return $mfnWebhookUrl;

            case Mode::TEST:
                $mfnWebhookUrl = (new AdminService)->getConfigKey(['key' => ConfigKey::RX_WEBHOOK_URL_FOR_MFN_TEST_MODE]);

                if (empty($mfnWebhookUrl) === true)
                {
                    $mfnWebhookUrl = Constant::MFN_DEFAULT_CUSTOM_WEBHOOK_URL_TEST_MODE;
                }

                return $mfnWebhookUrl;

            default:
                return Constant::MFN_DEFAULT_CUSTOM_WEBHOOK_URL;
        }
    }

    protected function createWebhookForMFN(string $merchantId)
    {
        $mfnWebhookUrl = $this->getWebhookUrlForMFN();

        $webhookCreateInput = [
            'url' => $mfnWebhookUrl,
            'events' => [
                'payout.processed'       => '1',
                'payout.failed'          => '1',
                'payout.reversed'        => '1',
                'payout.creation.failed' => '1',
            ],
        ];

        $this->trace->info(TraceCode::MFN_WEBHOOK_CREATE_REQUEST,
            [
                'merchant_id' => $merchantId,
                'input'       => $webhookCreateInput,
                'mode'        => $this->mode,
            ]);

        try
        {
            $response = $this->createForMerchant($webhookCreateInput);
        }
        catch(\Throwable $throwable)
        {
            if ($throwable->getCode() === ErrorCode::BAD_REQUEST_STORK_WEBHOOK_ALREADY_CREATED)
            {
                $exceptionData = $throwable->getData();

                $webhookId = $exceptionData['webhook_id'];

                $response = $this->update($webhookId, $webhookCreateInput);
            }
            else
            {
                throw $throwable;
            }
        }

        $this->trace->info(TraceCode::MFN_WEBHOOK_CREATE_SUCCESS,
            [
                'merchant_id' => $merchantId,
                'mode'        => $this->mode,
                'response'    => $response
            ]);
    }

    // we are temporarily blocking webhook creation for MFN feature enabled merchants, this will be removed later
    protected function blockWebhookCreationForMFN(Merchant\Entity $merchantEntity)
    {
        if ($merchantEntity->isFeatureEnabled(Feature\Constants::MFN) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WEBHOOK_DETAILS_LOCKED_FOR_MFN,
                null,
                [
                    'merchant_id'       => $this->merchant->getId()
                ]);
        }
    }

    public function listWebhookEvents(array $input): array
    {
        return (new Stork($this->mode, $this->product))->listWebhookEvents($input);
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForMerchantCreatedWebhook()
    {
        [$segmentEventName, $segmentProperties] = (new Merchant\Core())->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Webhook Added';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }
}
