<?php


namespace RZP\Services\Partnerships;

use App;
use Request;
use Throwable;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Constants\Environment;
use RZP\Models\Partner\Metric;
use RZP\Http\Request\Requests;
use RZP\Models\Order\ProductType;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\PartnershipServiceAsync;
use RZP\Models\Partner\Commission\Entity;
use RZP\Models\Partner\Commission\Invoice as CommissionInvoice;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\EntityOrigin\Core as EntityOriginCore;

class PartnershipsService extends Base\Service
{
    const CONTENT_TYPE_JSON           = 'application/json';

    const CREATE_RULE_GROUP           = '/twirp/rzp.commissions.rules.rule_group.v1.RuleGroupAPI/Create';

    const HEALTH_CHECK                = '/twirp/rzp.common.health.v1.HealthCheckAPI/Check';

    const GET_RULE_GROUP_BY_ID        = '/twirp/rzp.commissions.rules.rule_group.v1.RuleGroupAPI/Get';

    const GET_ALL_RULE_GROUP          = '/twirp/rzp.commissions.rules.rule_group.v1.RuleGroupAPI/List';

    const UPDATE_RULE_GROUP           = '/twirp/rzp.commissions.rules.v1.RuleGroupAPI/List';

    const CREATE_RULE                 = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/Create';

    const GET_RULE                    = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/Get';

    const UPDATE_RULE                 = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/Update';

    const GET_RULE_BY_RULE_GROUP      = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/GetByRuleGroup';

    const CREATE_RULE_CONFIG_MAPPING  = '/twirp/rzp.commissions.rules.rule_config_mapping.v1.RuleConfigMappingAPI/Create';

    const UPDATE_RULE_CONFIG_MAPPING  = '/twirp/rzp.commissions.rules.rule_config_mapping.v1.RuleConfigMappingAPI/Update';

    const CREATE_AUDIT_LOG            = '/twirp/rzp.commissions.audit.v1.AuditLogAPI/Create';

    const LIST_AUDIT_LOG_BY_ENTITY_IDS    = '/twirp/rzp.commissions.audit.v1.AuditLogAPI/ListByEntityIds';

    const LIST_AUDIT_LOG_BY_ENTITY_ID    = '/twirp/rzp.commissions.audit.v1.AuditLogAPI/ListByEntityId';

    const GET_LAST_PARTNER_MIGRATION   = '/twirp/rzp.commissions.partner_migration_audit.v1.PartnerMigrationAuditAPI/GetLastPartnerMigrationAudit';

    const CREATE_PARNTER_MIGRATION_AUDIT = '/twirp/rzp.commissions.partner_migration_audit.v1.PartnerMigrationAuditAPI/CreatePartnerMigrationAudit';

    const UPDATE_INVOICE_STATUS          = '/twirp/rzp.commissions.commission_invoice.v1.CommissionInvoiceAPI/UpdateInvoiceStatus';

    const UPDATE_PARTNER_CONFIG          = '/twirp/rzp.commissions.partner_config.v1.PartnerConfigAPI/Update';

    const DELETE_PARTNER_CONFIG          = '/twirp/rzp.commissions.partner_config.v1.PartnerConfigAPI/Delete';

    CONST UPDATE_MERCHANT_APPLICATION    = '/twirp/rzp.commissions.merchant_application.v1.MerchantApplicationAPI/Update';

    CONST DELETE_MERCHANT_APPLICATION    = '/twirp/rzp.commissions.merchant_application.v1.MerchantApplicationAPI/Delete';

    CONST UPDATE_MERCHANT_ACCESS_MAP     = '/twirp/rzp.commissions.merchant_access_map.v1.MerchantAccessMapAPI//Update';

    CONST DELETE_MERCHANT_ACCESS_MAP     = '/twirp/rzp.commissions.merchant_access_map.v1.MerchantAccessMapAPI/Delete';

    const ACTIVATED = 'ACTIVATED';

    const LOCALSTACK_ENVIRONMENTS = [Environment::BETA];

    const COMMISSION_DUAL_WRITE_QUEUE_CONFIG_KEY = 'prts_commission_create_dual_write';

    const COMMISSION_SHADOW_PHASE_QUEUE_CONFIG_KEY = 'prts_commission_create';

    const COMMISSION_CAPTURE_SHADOW_PHASE_QUEUE_CONFIG_KEY = 'prts_commission_capture';

    // Tells the client what the content type of the returned content actually is
    const CONTENT_TYPE = 'Content-Type';

    // Specifies the method or methods allowed when accessing the resource in response to a preflight request.
    const ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';

    // Used in response to a preflight request which includes the Access-Control-Request-Headers to indicate which HTTP headers can be used during the actual request.
    const ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';

    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';

    // Admin email parameter to be sent in all admin requests
    const ADMIN_EMAIL_PARAM_NAME = 'admin_email';
    const ADMIN_EMAIL_PARAM_HEADER = 'X-Admin-Email';

    const MAX_RETRY_COUNT = 2;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $requestTimeout;

    protected $env;

    protected $skipPassport;

    /**
     * @var string
     */
    protected string $baseLiveUrl;

    /**
     * @var string
     */
    protected string $baseTestUrl;

    public function __construct()
    {
        parent::__construct();

        $this->env          = $this->app['env'];
        $PartnershipsConfig = $this->app['config']['applications.partnerships'];

        $this->baseLiveUrl = $PartnershipsConfig['url']['live'];
        $this->baseTestUrl = $PartnershipsConfig['url']['test'];

        $this->key    = $PartnershipsConfig['username'];
        $this->secret = $PartnershipsConfig['secret'];

        $this->skipPassport = $PartnershipsConfig['skip_jwt_passport'];
        $this->requestTimeout = $PartnershipsConfig['request_timeout'];
    }

    public function createRuleGroup($parameters)
    {
        (new Validator())->validateInput(Validator::CREATE_RULE_GROUP, $parameters);

        return $this->sendRequest($parameters, self::CREATE_RULE_GROUP, Requests::POST);
    }

    public function getAllRuleGroup($parameters)
    {
        return $this->sendRequest($parameters, self::GET_ALL_RULE_GROUP, Requests::POST);
    }

    public function getRuleGroupById($parameters)
    {
        (new Validator())->validateInput(Validator::GET, $parameters);

        return $this->sendRequest($parameters, self::GET_RULE_GROUP_BY_ID, Requests::POST);
    }

    public function updateRuleGroup($parameters)
    {
        return $this->sendRequest($parameters, self::UPDATE_RULE_GROUP, Requests::POST);
    }

    public function createRule($parameters)
    {
        (new Validator())->validateInput(Validator::CREATE_RULE, $parameters);

        return $this->sendRequest($parameters, self::CREATE_RULE, Requests::POST);
    }

    public function getRule($parameters)
    {
        (new Validator())->validateInput(Validator::GET, $parameters);

        return $this->sendRequest($parameters, self::GET_RULE, Requests::POST);
    }

    public function getRuleByRuleGroupId($parameters)
    {
        return $this->sendRequest($parameters, self::GET_RULE_GROUP_BY_ID, Requests::POST);
    }

    public function updateRule($parameters)
    {
        (new Validator())->validateInput(Validator::UPDATE_RULE, $parameters);

        return $this->sendRequest($parameters, self::UPDATE_RULE, Requests::POST);
    }

    public function createRuleConfigMapping($parameters)
    {
        (new Validator())->validateInput(Validator::CREATE_RULE_CONFIG_MAPPING, $parameters);

        return $this->sendRequest($parameters, self::CREATE_RULE_CONFIG_MAPPING, Requests::POST);
    }

    public function updateRuleConfigMapping($parameters)
    {
        (new Validator())->validateInput(Validator::UPDATE_RULE_CONFIG_MAPPING, $parameters);

        return $this->sendRequest($parameters, self::UPDATE_RULE_CONFIG_MAPPING, Requests::POST);
    }

    public function createAuditLog($parameters, $mode)
    {
        (new Validator())->validateInput(Validator::CREATE_AUDIT_LOG, $parameters);

        return $this->sendRequest($parameters, self::CREATE_AUDIT_LOG, Requests::POST, $mode);
    }

    public function listAuditLogByEntityIds($parameters)
    {
        return $this->sendRequest($parameters, self::LIST_AUDIT_LOG_BY_ENTITY_IDS, Requests::POST);
    }

    public function listAuditLogByEntityId($parameters)
    {
        return $this->sendRequest($parameters, self::LIST_AUDIT_LOG_BY_ENTITY_ID, Requests::POST);
    }

    public function createPartnerMigrationAudit($parameters)
    {
        return $this->sendRequest($parameters, self::CREATE_PARNTER_MIGRATION_AUDIT, Requests::POST);
    }

    public function getLastPartnerMigration($parameters)
    {
        return $this->sendRequest($parameters, self::GET_LAST_PARTNER_MIGRATION, Requests::POST);
    }

    public function updateInvoiceStatusAsync($parameters, $partnerId)
    {
        try
        {
            if ($this->isPrtsInvoiceSyncEnabled($partnerId))
             {
                $path = self::UPDATE_INVOICE_STATUS;
                PartnershipServiceAsync::dispatch($parameters, $path);
             }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PRTS_UPDATE_INVOICE_STATUS_JOB_DISPATCHING_ERROR,
                $parameters
            );
        }
    }

    public function upsertPartnerConfig($parameters)
    {
        return $this->sendRequestWithRetry($parameters, self::UPDATE_PARTNER_CONFIG, Requests::POST);
    }

    public function deletePartnerConfig($parameters)
    {
        return $this->sendRequestWithRetry($parameters, self::DELETE_PARTNER_CONFIG, Requests::POST);
    }

    public function upsertMerchantApplication($parameters)
    {
        return $this->sendRequestWithRetry($parameters, self::UPDATE_MERCHANT_APPLICATION, Requests::POST);
    }

    public function deleteMerchantApplication($parameters)
    {
        return $this->sendRequestWithRetry($parameters, self::DELETE_MERCHANT_APPLICATION, Requests::POST);
    }

    public function upsertMerchantAccessMap($parameters)
    {
        return $this->sendRequestWithRetry($parameters, self::UPDATE_MERCHANT_ACCESS_MAP, Requests::POST);
    }

    public function deleteMerchantAccessMap($parameters)
    {
        return $this->sendRequestWithRetry($parameters, self::DELETE_MERCHANT_ACCESS_MAP, Requests::POST);
    }

    /**
     * Dual write Commission in Partnerships service by pushing job to the queue using pushRaw.
     * @param   Entity  $commission The commission
     * @return  void
     */
    public function createCommissionDualWrite(Entity $commission): void
    {
        try
        {
            if(! $this->isDualWriteExpEnabled($commission))
            {
                return;
            }

            $commissionComponent = $this->repo->commission_component->findByCommissionId($commission->getId())->first();
            $data       = [
                'commission'           => $commission->attributesToArray(),
                'commission_component' => optional($commissionComponent)->toArray()
            ];

            $data['commission']['notes'] = (object) ($data['commission']['notes']);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($data) {

                $this->pushRawJob($data, self::COMMISSION_DUAL_WRITE_QUEUE_CONFIG_KEY);

                $this->trace->count(Metric::PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH, ['event_name' => 'commission_dual_write', 'success' => true]);
            }));
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PRTS_COMMISSION_DUAL_WRITE_FAILED,
                [ $commission->toArrayPublic() ]
            );

            $this->trace->count(Metric::PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH, ['event_name' => 'commission_dual_write', 'success' => false]);
        }
    }

    /**
     * @param string $partnerId
     * @param array  $commissionIds
     * This function is used to dispatch the commission capture event to PRTS service when admin actions are triggered
     */
    public function dispatchCommissionCaptureToPRTS(string $partnerId, array $commissionIds): void
    {
        try
        {
            if (!$this->isShadowCommissionPhaseExpEnabled($partnerId))
            {
                return;
            }

            $data = [
                'commission_ids' => $commissionIds
            ];

            \Event::dispatch(new TransactionalClosureEvent(function() use ($data) {
                // Job will be dispatched only if the transaction commits.
                $this->pushRawJob($data, self::COMMISSION_CAPTURE_SHADOW_PHASE_QUEUE_CONFIG_KEY);

                $this->trace->count(Metric::PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH, ['event_name' => 'commission_capture', 'success' => true]);
            }));
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PRTS_COMMISSION_SHADOW_PHASE_FAILED,
                $data
            );
            $this->trace->count(Metric::PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH, ['event_name' => 'commission_capture', 'success' => false]);
        }
    }

    /**
     * Creates Commission in Partnerships service in Shadow Phase by pushing job to the queue using pushRaw.
     *
     * @param array            $commissions The commissions.
     * @param PaymentEntity    $payment     The payment entity.
     *
     * @return  void
     */
    public function createCommissionShadowPhase(array $commissions, PaymentEntity $payment): void
    {
        try
        {
            if (empty($commissions) == true || !$this->isShadowCommissionPhaseExpEnabled($commissions[0][Entity::PARTNER_ID]))
            {
                return;
            }

            $payload = CommissionCreateEventDataUtil::getPayloadForCommissionCreate($commissions, $payment);

            \Event::dispatch(new TransactionalClosureEvent(function() use ($payload) {
                // Job will be dispatched only after the transaction commits.
                $this->pushRawJob($payload, self::COMMISSION_SHADOW_PHASE_QUEUE_CONFIG_KEY);

                $this->trace->count(Metric::PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH, ['event_name' => 'commission_create', 'success' => true]);
            }));
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PRTS_COMMISSION_SHADOW_PHASE_FAILED,
                [$payment->toArrayPublic(), $commissions]
            );
            $this->trace->count(Metric::PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH, ['event_name' => 'commission_create', 'success' => false]);
        }
    }

    /**
     * Dispatch commission invoice event to partnership service
     *
     * @param CommissionInvoice\Entity $invoice
     * @param int                                        $month
     * @param int                                        $year
     * @param bool                                          $regenerateInvoice
     *
     * @return void
     */
    public function createInvoiceShadowPhase(CommissionInvoice\Entity $invoice, int $month, int $year, bool $regenerateInvoice)
    {
        try
        {
            if (!$this->isPrtsInvoiceSyncEnabled($invoice->getMerchantId()))
            {
                return;
            }
            $invoicePayload = [
                'partner_id'       => $invoice->getMerchantId(),
                'month'            => $month,
                'year'             => $year,
                'invoice_id'       => $invoice->getId(),
                'force_regenerate' => $regenerateInvoice,
            ];
            $jobPayload = [
                'payload'     => json_encode($invoicePayload),
                'event_name'  => 'GENERATE_INVOICE',
            ];
            \Event::dispatch(new TransactionalClosureEvent(function() use ($jobPayload) {
                try
                {
                    // Job will be dispatched only after the transaction commits.
                    $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_DISPATCHING,
                                       [
                                           'mode'     => $this->mode,
                                           'payload'  => $jobPayload,
                                       ]
                    );
                    $messageId = $this->pushRawJob($jobPayload, 'prts_common');
                    $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_DISPATCHED, [
                        'payload'        => $jobPayload,
                        'messageId' => $messageId
                    ]);
                    $this->trace->count(Metric::PRTS_COMMISSION_INVOICE_PUSH,['success'=> true]);
                }
                catch (\Exception $ex)
                {
                    $this->trace->error(TraceCode::PRTS_COMMISSION_INVOICE_DISPATCHING_ERROR, [
                        'error'      => $ex->getMessage(),
                        'job_payload' => $jobPayload,
                    ]);
                    $this->trace->count(Metric::PRTS_COMMISSION_INVOICE_PUSH,['success'=> false]);
                }
            }));
        }
        catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::PRTS_COMMISSION_INVOICE_DISPATCHING_ERROR, [
               'error'      => $ex->getMessage(),
               'invoice_id' => $invoice->getId(),
            ]);
            $this->trace->count(Metric::PRTS_COMMISSION_INVOICE_PUSH,['success'=> false]);
        }
    }

    private function isDualWriteExpEnabled(Entity $commission): bool
    {
        $properties = [
            'id'            => $commission->getAttribute(Entity::PARTNER_ID),
            'experiment_id' => $this->app['config']->get('app.prts_commission_dual_write_exp_id'),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable(
            $properties, 'enable', TraceCode::PRTS_COMMISSION_DUAL_WRITE_SPLITZ_ERROR
        );
    }

    private function isShadowCommissionPhaseExpEnabled(string $partnerID): bool
    {
        $properties = [
            'id'            => $partnerID,
            'experiment_id' => $this->app['config']->get('app.prts_commission_shadow_phase_exp_id'),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable(
            $properties, 'enable', TraceCode::PRTS_COMMISSION_SHADOW_PHASE_SPLITZ_ERROR
        );
    }

    /**
     * Checks whether merchant is allowed for partnership service sync.
     *
     * @param string $merchantId
     *
     * @return bool
     */
    private function isPrtsInvoiceSyncEnabled(string $merchantId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.prts_commission_invoice_shadow_phase_exp_id'),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * Pushes the job to the SQS queue based on the queue config key and connection.
     * @param   $data
     * @param   $queueConfigKey
     *
     * @return  string
     */
    public function pushRawJob(array $data, string $queueConfigKey): string
    {
        $queueName = $this->app['config']->get('queue.' . $queueConfigKey . '.' . $this->app['rzp.mode']);
        $connection = $this->getQueueConnection();

        return $this->app['queue']->connection($connection)->pushRaw(json_encode($data), $queueName);
    }

    /**
     * Fetches the queue connection to use. If environment is devstack, localstack is used.
     */
    private function getQueueConnection(): string
    {
        if (in_array(app('env'), self::LOCALSTACK_ENVIRONMENTS, true) === true)
        {
            return 'sqs_localstack';
        }
        else
        {
            return 'sqs';
        }
    }

    /**
     * @throws Exception\InvalidPermissionException
     * @throws Exception\ServerErrorException
     */
    public function sendAdminRequest($parameters, $path, $method): array
    {
        $admin = $this->auth->getAdmin();
        if ($admin === null)
        {
            throw new Exception\InvalidPermissionException('admin authorization required');
        }
        $adminEmail                               = $admin->getEmail() ?? '';
        $parameters[self::ADMIN_EMAIL_PARAM_NAME] = $adminEmail;

        return $this->sendRequest($parameters, $path, $method);
    }

    public function sendRequest($parameters, $path, $method, $mode = null)
    {
        $requestParams = $this->getRequestParams($parameters, $path, $method, $mode);

        try {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            return $this->parseAndReturnResponse($response);
        } catch (Throwable $e) {
            $this->trace->error(TraceCode::PARTNERSHIPS_REQUEST_ERROR, [
                'error'      => $e->getMessage(),
                'parameters' => $parameters,
                'path'       => $path,
                '$response'  => $e->getData()
            ]);
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_PARTNERSHIPS_FAILURE, $e->getData(), $e);
        }
    }

    /**
     * calls send request with retry logic
     * @param $parameters
     * @param $path
     * @param $method
     * @param $mode
     *
     * @return array|null
     */
    public function sendRequestWithRetry($parameters, $path, $method, $mode = null)
    {
        $attempts = 0;
        $response = null;
        do {
            try
            {
                $response = $this->sendRequest($parameters, $path, $method, $mode);
                break;
            } catch (Throwable $e) {
                $responseData =  $e->getData();
                if(empty($responseData['status_code']) === false && $responseData['status_code'] < 500)
                {
                    break;
                }
                $attempts++;
            }
        } while($attempts < self::MAX_RETRY_COUNT);

        return $response;
    }

    public function getRequestParams($parameters, $path, $method, $mode = null)
    {
        if ($mode === null)
        {
            $this->mode = $this->app['rzp.mode'];
        }
        else
        {
            $this->mode = $mode;
        }
        $url = $this->getBaseUrl() . $path;

        $headers = [];

        $parameters = json_encode($parameters);

        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;
        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);

        $headers[self::ADMIN_EMAIL_PARAM_HEADER] = $parameters[self::ADMIN_EMAIL_PARAM_NAME] ?? '';
        $options = [
            'timeout' => $this->requestTimeout,
        ];

        $jwt = null;
        if ($this->skipPassport === false) {
            $jwt = $this->auth->getPassportJwt($this->getBaseUrl());
        }
        if ($jwt == null) {
            $options['auth'] = [$this->key, $this->secret];
        }
        $headers[self::X_PASSPORT_JWT_V1] = $jwt;

        $this->trace->info(TraceCode::PARTNERSHIPS_REQUEST, ['url' => $url, 'parameters' => $parameters]);

        return [
            'url'       => $url,
            'headers'   => $headers,
            'data'      => $parameters,
            'options'   => $options,
            'method'    => $method,
        ];
    }

    private function getBaseUrl(): string
    {
        // returning live url for now as entities are not sync in live and test mode
        return $this->baseLiveUrl;
        //if($this->mode === Mode::LIVE) {
        //    return $this->baseLiveUrl;
        //}
        //else
        //{
        //    return $this->baseTestUrl;
        //}
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $resBody = json_decode($res->body, true);

        $partnershipsServiceResponse = ['status_code' => $code, 'response' => $resBody!=null? $resBody: $res->body ];

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\RuntimeException('Malformed json response', $partnershipsServiceResponse);
        }

        $this->trace->info(TraceCode::PARTNERSHIPS_REQUEST, $partnershipsServiceResponse);

        return $partnershipsServiceResponse;
    }
}
