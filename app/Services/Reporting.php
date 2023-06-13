<?php

namespace RZP\Services;

use App;
use Request;
use RZP\Http\Request\Requests;
use \WpOrg\Requests\Response;
use \WpOrg\Requests\Exception as Requests_Exception;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;

use RZP\Services\Mock;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Http\RequestHeader;
use RZP\Models\Partner\Config;
use RZP\Models\Merchant\Account;
use RZP\Models\Partner\Commission;
use RZP\Models\Base\PublicCollection;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Feature\Service as FeatureService;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Services\Reporting\Constants;
use RZP\Models\Admin\Role\TenantRoles;
use RZP\Services\Reporting\Validators\Factory as ValidationFactory;

/**
 * Interface for api to talk to Reporting service
 */
class Reporting implements ExternalService
{
    const REQUEST_TIMEOUT = 30; // In secs

    /**
     * Path for various endpoints
     */
    const CONFIG_PATH              = '/v1/configs';
    const LOG_PATH                 = '/v1/logs';
    const ADMIN_LOG_PATH           = '/v1/admin-logs';
    const SCHEDULE_PATH            = '/v1/schedules';
    const SCHEDULE_PATH_V2         = '/v2/schedules';
    const LOG_PATH_FOR_MERCHANT    = '/v1/merchant/logs';
    const RESTRICTIONS_PATH        = '/v1/consumer_restrictions';

    const SCHEDULE_PREFIX = 'sched_';

    const LOGS          = 'logs';
    const CONFIGS       = 'configs';
    const SCHEDULES     = 'schedules';

    // REPORT_TYPE constants
    const MERCHANT      = 'merchant';
    const PARTNER       = 'partner';
    const RAZORPAYX     = 'razorpayx';

    //CA TRANSACTION constants
    const RAW_SQL            = 'raw_sql';
    const FILTERS            = 'filters';
    const CA_TRANSACTIONS    = 'ca_transactions';
    const TEMPLATE_OVERRIDES = 'template_overrides';
    const QUERY_PARAMS       = 'query_params';

    // OrgAdminReport Constants
    const DASHBOARD_HOST_NAME = 'dashboard_host_name';

    // Headers
    const CONSUMER_HEADER               = 'X-Consumer';
    const REPORT_TYPE_HEADER            = 'X-Report-Type';
    const ADMIN_TOKEN_HEADER            = 'X-Admin-Token';
    const LINKED_ACCOUNT_HEADER         = 'X-Linked-Account-Parent';
    const USER_ID_HEADER                = 'X-Dashboard-User-Id';
    const ORG_ID_HEADER                 = 'X-Org-Id';
    const GENERATED_BY_HEADER           = 'X-Generated-By';
    const BATCH_ID                      = 'X-Batch-Id';
    const ASSOCIATED_FEATURES_HEADER    = 'X-Merchant-Features';
    const TENANT_ROLE                   = 'X-Tenant-Role';

    const REPORTING_SERVICE_RESPONSE_FAILURE_TOTAL = 'reporting_service_response_failure_total';
    const REPORTING_SERVICE_RESPONSE_SUCCESS_TOTAL = 'reporting_service_response_success_total';

    const MERCHANT_REPORT_TYPES = [
        self::MERCHANT,
        self::RAZORPAYX,
        self::PARTNER,
    ];

    const ADMIN_ROUTES_WITH_MERCHANT_REPORT_TYPE = [
        'reporting_config_create_full',
        'reporting_config_edit_full',
        'reporting_config_edit_bulk'
    ];

    const RZP_ADMIN_ROUTES_WITH_MERCHANT_REPORT_TYPE = [
        'reporting_config_create_admin',
        'reporting_config_list_admin',
        'reporting_config_edit_admin',
        'reporting_config_delete_admin'
    ];

    /**
     * @var array
     */
    protected $config;

    protected $trace;

    protected $mode;

    protected $headers;

    protected  $app;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;
    /**
     * @var mixed
     */
    private $repo;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app    = $app;
        $this->config = $app['config']['applications.reporting'];
        $this->trace  = $app['trace'];
        $this->mode   = $app['rzp.mode'];
        $this->repo   = $app['repo'];

        // TODO: This service should(to discuss) not depend on BA, better to pass
        // or set merchant context on the instance before using.
        $this->ba     = $app['basicauth'];

        $this->setHeaders();
    }

    protected function setHeaders()
    {
        /**
         * Read about how to API interacts with reporting service:
         * https://github.com/razorpay/api/wiki/Reporting-Service
         */
        $headers = [];
        //user id
        if(Request::header(self::USER_ID_HEADER) !== NULL)
        {
            $headers[self::USER_ID_HEADER] = Request::header(self::USER_ID_HEADER);
        }

        // Proxy Auth
        $merchantId = $this->ba->getMerchantId();

        $linkedAccountParentId = $this->getLinkedAccountParentId();

        // Auth w/ Admin Token
        $adminToken = $this->ba->getAdminToken();

        // Report type header coming from client
        $reportType = Request::header(self::REPORT_TYPE_HEADER);
        $consumer = Request::header(self::CONSUMER_HEADER);

        if (empty($merchantId) === false)
        {
            // This is to be used for merchant reports only

            // Add OrgId header of merchant for org level configs
            $orgId = $this->ba->getOrgId();
            $headers[self::ORG_ID_HEADER] = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            if ($merchantId === Account::SHARED_ACCOUNT)
            {
                // If SHARED_ACCOUNT, use headers sent
                // Useful for creating schedules for non-merchants
                $headers[self::REPORT_TYPE_HEADER] = $reportType ?: self::MERCHANT;
                $headers[self::CONSUMER_HEADER]    = $consumer ?: Account::SHARED_ACCOUNT;
            }
            else
            {
                // MERCHANT Reports
                // Proxy Auth used here
                $this->validateMerchantReportType($reportType);

                $headers[self::REPORT_TYPE_HEADER] = $reportType ?: self::MERCHANT;
                $headers[self::CONSUMER_HEADER]    = $merchantId;
            }

            if (empty($linkedAccountParentId) === false)
            {
                $headers[self::LINKED_ACCOUNT_HEADER] = $linkedAccountParentId;
            }

            // Add admin token if available
            if (empty($adminToken) === false)
            {
                $headers[self::ADMIN_TOKEN_HEADER] = $adminToken;
            }
        }
        else if (empty($adminToken) === false)
        {
            // This is to be used for non merchant reports only

            // Added OrgId header of admin, needed for log creation not being used further
            $orgId = $this->ba->getAdmin()->getOrgId();
            $headers[self::ORG_ID_HEADER] = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            // Entity view in dashboard
            $headers[self::ADMIN_TOKEN_HEADER] = $adminToken;

            $this->failIfAdminTryingToDownloadMerchantReports($reportType);

            if ((empty($reportType) === false) and
                (empty($consumer) === false))
            {
                $headers[self::REPORT_TYPE_HEADER] = $reportType;
                $headers[self::CONSUMER_HEADER] = $consumer;
            }

            $this->validateRequestFromOrg($consumer);
        }

        if (empty(Request::header(RequestHeader::DEV_SERVE_USER)) === false)
        {
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        $this->headers = $headers;
    }

    public function fetchMultiple(string $entity, array $input)
    {
        switch ($entity)
        {
            case self::LOGS:
                return $this->fetchLogMultipleAdmin($input);

            case self::CONFIGS:
                return $this->fetchConfigMultipleAdmin($input);

            case self::SCHEDULES:
                return $this->fetchScheduleMultipleAdmin($input);
        }

        return [];
    }

    public function fetch(string $entity, string $id, array $input)
    {
        switch ($entity)
        {
            case self::LOGS:
                return $this->fetchLogByIdAdmin($id);

            case self::CONFIGS:
                return $this->fetchConfigByIdAdmin($id);

            case self::SCHEDULES:
                return $this->fetchScheduleByIdAdmin($id);
        }

        return [];
    }

    /*
        Proxy Auth
    */

    public function createConfig(array $input): array
    {
        $this->validateFeatures($input);

        return $this->createAndSendRequest(Requests::POST, self::CONFIG_PATH, $input);
    }

    public function createConfigAdmin(array $input): array
    {
        $this->verifyIfAdminPerformingOrgReportConfig();

        return $this->createConfig($input);
    }

    public function createFullConfig(array $input): array
    {
        $path = self::CONFIG_PATH . '/full';

        $this->validateFeatures($input);

        return $this->createAndSendRequest(Requests::POST, $path, $input);
    }

    public function fetchConfigMultiple(array $input): array
    {
        $this->addAssociatedFeaturesHeader();

        $configs = $this->createAndSendRequest(Requests::GET, self::CONFIG_PATH, $input);

        return $this->filterConfigsByFeatureAndTags($configs);
    }

    public function fetchConfigById(string $id): array
    {
        $path = self::CONFIG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function editConfig(string $id, array $input): array
    {
        $path = self::CONFIG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::PATCH, $path, $input);
    }

    public function editConfigAdmin(string $id, array $input): array
    {
        $this->verifyIfAdminPerformingOrgReportConfig();

        return $this->editConfig($id, $input);
    }

    public function editFullConfig(string $id, array $input): array
    {
        $path = self::CONFIG_PATH . '/' . $id . '/full';

        return $this->createAndSendRequest(Requests::PATCH, $path, $input);
    }

    public function editBulkConfig(array $input): array
    {
        $path = self::CONFIG_PATH . '/bulk';

        return $this->createAndSendRequest(Requests::POST, $path, $input);
    }

    public function deleteConfig(string $id): array
    {
        $path = self::CONFIG_PATH . '/' . $id;

        $response = $this->createAndSendRequest(Requests::DELETE, $path);

        if (isset($response['error']) === false)
        {
            $scheduleIds = $response['schedule_ids'] ?? [];

            foreach ($scheduleIds as $scheduleId)
            {
                $this->deleteScheduleTask($scheduleId);
            }
        }

        return $response;
    }

    public function deleteConfigAdmin(string $id): array
    {
        $this->verifyIfAdminPerformingOrgReportConfig();

        return $this->deleteConfig($id);
    }

    public function createLog(array $input): array
    {
        //
        // Adds mode to create log input. Mode is only relavent in this endpoint
        // (creating log) as log entity's mode attribute is used to query
        // respective database of api. Config is mode independent in reporting service.
        //
        $input['mode'] = $this->mode;

        /**
         * If request is coming from the proxy auth (merchant)
         * Adds the merchant id of the merchant who initiated the request
         * irrespective of the case whether the request was for the merchant itself
         * or for one of it's linked account
         */
        if ($this->ba->isProxyAuth())
        {
            $input['generated_by'] = $this->ba->authCreds->getKey();
        }

        if ($this->ba->isAdminAuth() and
            ($this->ba->getAdmin()->getOrgId() === Org\Entity::RAZORPAY_ORG_ID))
        {
            $adminRoles = $this->ba->getPassport()['roles'] ?? [];

            if (empty($adminRoles) === true)
            {
                $this->trace->info(TraceCode::TENANT_ENTITY_NO_ADMIN_ROLES_SET);
//                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
            }
            else
            {
                $tenantRole = '';

                if (in_array(TenantRoles::ENTITY_PAYMENTS, $adminRoles) === true)
                {
                    $tenantRole = TenantRoles::ENTITY_PAYMENTS;
                }
                elseif (in_array(TenantRoles::ENTITY_BANKING, $adminRoles) === true)
                {
                    $tenantRole = TenantRoles::ENTITY_BANKING;
                }

                $this->headers[self::TENANT_ROLE] = $tenantRole;
            }
        }

        if (Request::header(self::BATCH_ID) !== null)
        {
            $input['batch_id'] = Request::header(self::BATCH_ID);
        }

        $path = self::LOG_PATH;

        if (empty($input['emails']) === true)
        {
            $path .= '?send_email=false';
        }

        if ((empty($input['transactions_report_type']) === false) and ($input['transactions_report_type'] === self::CA_TRANSACTIONS))
        {
            unset($input['transactions_report_type']);

            $input = $this->buildCaTransactionRawQueryParams($input);
        }

        $this->validateInput($input);

        if ($this->ba->isAdminAuth() === true and
            ($this->ba->getAdmin()->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID))
        {
            $this->addOrgHostname($input);
        }

        return $this->createAndSendRequest(Requests::POST, $path, $input);
    }

    public function editLog(string $id, array $input): array
    {
        $path = self::LOG_PATH . '/' . $id;

        $this->validateInput($input);

        return $this->createAndSendRequest(Requests::PATCH, $path, $input);
    }

    public function fetchLogById(string $id): array
    {
        $path = self::LOG_PATH . '/' . $id;

        if (($this->ba->isAppAuth() === true) and
            ($this->ba->isAdminAuth() === true))
        {
            $path = self::ADMIN_LOG_PATH . '/' . $id;
        }

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function fetchLogMultiple(array $input): array
    {
        $path = self::LOG_PATH;

        $headers = [];

        if ($this->ba->isProxyAuth())
        {
            $headers = [
                self::GENERATED_BY_HEADER   => $this->ba->authCreds->getKey(),
            ];

            $path = self::LOG_PATH_FOR_MERCHANT;
        }

        return $this->createAndSendRequest(Requests::GET, $path, $input, $headers);
    }

    public function createSchedule(array $input): array
    {
        $reportingServiceRequest = $input['payload'];

        $scheduleRequest = $input['schedule'];
        $adminToken = $this->ba->getAdminToken();
        if (empty($adminToken) === false)
        {
            $path = self::SCHEDULE_PATH;
            $this->trace->info(TraceCode::REPORTING_SERVICE_CREATE_SCHEDULE, $reportingServiceRequest);
            $response = $this->createScheduleOnReportingService($reportingServiceRequest,$path);

            $apiResponse = null;

            // In case reporting service returns error, then we dont create schedule/schedule task
            if (isset($response['error']) === false)
            {
                $this->trace->info(TraceCode::REPORTING_SERVICE_CREATE_SCHEDULE, $input);

                // Need to store entity_id without sign.
                $scheduleRequest[ScheduleTask\Entity::ENTITY_ID] = $this->generateEntityId($response['id']);

                $apiResponse = $this->createScheduleOnApi($scheduleRequest);

                // Link Api schedule with reporting schedule
                $response = $this->linkSingleScheduledTasks($response);
            }

            if (empty($apiResponse) === true)
            {
                throw new Exception\DbQueryException(
                    'Failed to create schedule');
            }

        }
        else{
            $path = self::SCHEDULE_PATH_V2;
            $response = $this->createScheduleOnReportingService($reportingServiceRequest,$path);
        }
        return $response;
    }

    public function fetchScheduleMultiple(array $input): array
    {

        $adminToken = $this->ba->getAdminToken();
        if (empty($adminToken) === false)
        {
            $path = self::SCHEDULE_PATH;
            $scheduleDataList = $this->createAndSendRequest(Requests::GET, $path, $input);

            $scheduleTaskData = $this->linkAllScheduledTasks($scheduleDataList);
        }
        else{
            $path = self::SCHEDULE_PATH_V2;

            $scheduleTaskData = $this->createAndSendRequest(Requests::GET, $path, $input);
        }


        return $scheduleTaskData;

    }

    public function fetchScheduleById(string $id): array
    {
        $adminToken = $this->ba->getAdminToken();
        if (empty($adminToken) === false)
        {
            $path = self::SCHEDULE_PATH . '/' . $id;
        }
        else{
            $path = self::SCHEDULE_PATH_V2 . '/' . $id;
        }

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function deleteSchedule(string $id): array
    {
        $adminToken = $this->ba->getAdminToken();
        if (empty($adminToken) === false)
        {
            $path = self::SCHEDULE_PATH . '/' . $id;
            $response = $this->createAndSendRequest(Requests::DELETE, $path);

            // Deleting the corresponding schedule task as well.
            if (isset($response['error']) === false)
            {
                $this->deleteScheduleTask($id);
            }
        }
        else{
            $path = self::SCHEDULE_PATH_V2 . '/' . $id;
            $response = $this->createAndSendRequest(Requests::DELETE, $path);
        }

        return $response;
    }
    public function updateSchedule(string $id, array $input): array
    {

       $path = self::SCHEDULE_PATH_V2 . '/' . $id;

        $reportingServiceRequest = $input['payload'];

        $this->traceReportingServiceRequest($reportingServiceRequest);

        $response = $this->createAndSendRequest(Requests::PATCH, $path, $reportingServiceRequest);


        return $response;
    }

    /**
     *  Delete schedule task
     */
    protected function deleteScheduleTask(string $id)
    {
        $entityId = $this->generateEntityId($id);

        $scheduleTask = $this->repo->schedule_task->fetchByEntity($entityId);

        if (empty($scheduleTask) === false)
        {
            $this->repo->deleteOrFail($scheduleTask);
        }
    }

    public function processTasks(PublicCollection $scheduleTasks): array
    {
        if (count($scheduleTasks) === 0)
        {
            return [];
        }

        $response = $this->triggerSchedule($scheduleTasks);

        // If there is no error then
        // 1. Strip sign for all ids, as api doesn't know the signs for schedule entity
        // 2. For all the success cases, update the next run

        if (isset($response['error']) === false)
        {
            $response = array_map(function($entityIds) {
                            return array_map(function($entityId){
                                        return $this->generateEntityId($entityId);
                            }, $entityIds);
            }, $response);

            $successIds = $response['success_ids'];

            // We need to get all success_ids and mark their next run.
            foreach ($successIds as $successId)
            {
                $scheduleTask = $this->repo->schedule_task->fetchByEntity($successId);

                $scheduleTask->updateNextRunAndLastRun();

                $this->repo->saveOrFail($scheduleTask);
            }
        }

        return $response;
    }

    public function fetchLogByIdAdmin(string $id): array
    {
        $path = self::LOG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function fetchConfigByIdAdmin(string $id): array
    {
        $path = self::CONFIG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function fetchScheduleByIdAdmin(string $id): array
    {
        $path = self::SCHEDULE_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function fetchLogMultipleAdmin(array $input): array
    {
        $headers = $this->fetchHeadersFromInput($input);

        return $this->createAndSendRequest(Requests::GET, self::LOG_PATH, $input, $headers);
    }

    public function getConsumerRestrictions(): array
    {
        $path = self::RESTRICTIONS_PATH;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    // TODO: Add filter based upon feature/tags for admin calls
    public function fetchConfigMultipleAdmin(array $input): array
    {
        $headers = $this->fetchHeadersFromInput($input);

        return $this->createAndSendRequest(Requests::GET, self::CONFIG_PATH, $input, $headers);
    }

    protected function fetchHeadersFromInput(array $input)
    {
        $consumer    = $input['consumer'] ?? Account::SHARED_ACCOUNT;
        $reportType = $input['report_type'] ?? 'merchant';

        return [
            self::CONSUMER_HEADER    => $consumer,
            self::REPORT_TYPE_HEADER => $reportType,
        ];
    }

    public function fetchScheduleMultipleAdmin(array $input): array
    {
        $headers = $this->fetchHeadersFromInput($input);

        return $this->createAndSendRequest(Requests::GET, self::SCHEDULE_PATH, $input, $headers);
    }

    protected function createScheduleOnApi(array $input)
    {
        $entityId = $input[ScheduleTask\Entity::ENTITY_ID];

        // Since schedule tasks can be created only for a merhcant
        // We'll always use basic auth to fetch merhcant object
        $merchant = $this->ba->getMerchant();

        // As discussed, we will not be creating new schedule
        // Schedule id will be passed in request object
        $scheduleTaskRequest = [
            ScheduleTask\Entity::ENTITY_ID   => $entityId,
            ScheduleTask\Entity::TYPE        => ScheduleTask\Type::REPORTING,
            ScheduleTask\Entity::ENTITY_TYPE => ScheduleTask\Type::LOG,
            ScheduleTask\Entity::SCHEDULE_ID => $input[ScheduleTask\Entity::SCHEDULE_ID],
        ];

        $scheduleTask = (new ScheduleTask\Core)->createForExternalService($merchant, $scheduleTaskRequest);

        return $scheduleTask->toArrayPublic();
    }

    protected function createScheduleOnReportingService(array $input,string $path): array
    {

        $response = $this->createAndSendRequest(Requests::POST, $path, $input);

        return $response;
    }

    protected function triggerSchedule(PublicCollection $scheduleTasks): array
    {
        $payload = [];

        foreach ($scheduleTasks as $scheduleTask)
        {
            $payload[] = [
                'id'          => self::SCHEDULE_PREFIX . $scheduleTask->getEntityId()
            ];
        }

        // Mode is necessary to trigger a schedule
        // Depending upon mode, the corresponding test/live data would be fetched
        $request = [
            'mode'    => $this->mode,
            'payload' => $payload,
        ];

        $path = self::SCHEDULE_PATH . '/trigger';

        $headers = [];
        $headers[self::REPORT_TYPE_HEADER] = self::MERCHANT;
        $headers[self::CONSUMER_HEADER] = Account::SHARED_ACCOUNT;

        return $this->createAndSendRequest(Requests::POST, $path, $request, $headers);
    }

    protected function generateEntityId(string $entityId)
    {
        return explode(self::SCHEDULE_PREFIX, $entityId)[1];
    }

    public function createAndSendRequest(
        string $method,
        string $path,
        array $input = [],
        array $headers = []): array
    {
        //
        // In case reporting is to be mocked, don't make
        // any external call and just return empty array.
        //
        if ($this->config['mock'] === true)
        {
            return (new Mock\ReportingService)->getReportingConfig();
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $request = [
            'url'     => $this->config['url'] . $path,
            'method'  => $method,
            'content' => $input,
            'options' => $options,
            'headers' => array_merge($this->headers, $headers)
        ];

        $this->traceReportingServiceRequest($request);

        $response = $this->sendRequest($request);

        $this->traceReportingServiceResponse($response);

        return json_decode($response->body, true);
    }

    protected function sendRequest(array $request)
    {
        try
        {
            $request['headers']['Content-Type'] = 'application/json';

            // json encode if data is must, else ignore.
            if (in_array($request['method'], [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
            {
                $request['content'] = json_encode($request['content'], JSON_FORCE_OBJECT);
            }

            $response = Requests::request(
                            $request['url'],
                            $request['headers'],
                            $request['content'],
                            $request['method'],
                            $request['options']);

            $this->validateResponse($response);

            $dimension = [
                'request-type' => $request['method']
            ];

            $this->trace->count(self::REPORTING_SERVICE_RESPONSE_SUCCESS_TOTAL, $dimension);

            return $response;

        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REPORTING_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            $this->trace->count(self::REPORTING_SERVICE_RESPONSE_FAILURE_TOTAL);

            throw new Exception\IntegrationException('
                Could not receive proper response from reporting service');
        }
    }

    /**
     * Returns auth headers to be used to make requests to external reporting
     * service.
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        return [
            $this->config['username'],
            $this->config['secret'],
        ];
    }

    /**
     * Currently, all the merchant reports, and shared reports are returned from
     * reporting service. In this method we have some logic to filter out above
     * such additional report configs basis merchant.
     *
     * @param  array  $configs
     *
     * @return array
     */
    protected function filterConfigsByFeatureAndTags(array $configs): array
    {
        //
        // $item is a collection for easy operations. Also $configs is empty
        // in case reporting is mocked.
        //
        $items = collect($configs['items'] ?? []);

        $this->trace->info(TraceCode::REPORTING_SERVICE_UNFILTERED_CONFIGS,
            [
                'count'     => $items->count(),
                'items'     => $items->pluck('name', 'id')->toArray(),
            ]);

        $merchant = $this->ba->getMerchant();

        // Don't filter anything for non merchants
        if (empty($merchant) === true)
        {
            return $configs;
        }

        $items = $this->filterOnReportTypeAndFeatures($merchant, $items);

        $items = $this->filterOnReportTypeAndNameAndConsumer($merchant, $items);

        $items = $this->filterForBusinessBanking($merchant, $items);

        $items = $this->filterOnAdminAuth($merchant, $items);

        $this->trace->info(TraceCode::REPORTING_SERVICE_FILTERED_CONFIGS,
            [
                'count'     => $items->count(),
                'items'     => $items->pluck('name', 'id')->toArray(),
            ]);

        $configs['items'] = $items->values()->all();
        $configs['count'] = $items->count();

        return $configs;
    }

    protected function filterOnReportTypeAndFeatures(Merchant\Entity $merchant, $items)
    {
        $tags     = array_map('strtolower', $merchant->tagNames());
        $features = $merchant->getEnabledFeatures();

        $hasPlTag                          = in_array('payment_link_report', $tags, true);
        $hasMarketplaceFeature             = in_array(Feature::MARKETPLACE, $features, true);
        $hasOpenwalletFeature              = in_array(Feature::OPENWALLET, $features, true);
        $hasMarketplaceOrOpenwalletFeature = ($hasMarketplaceFeature or $hasOpenwalletFeature);
        $hasChargeAtWillFeature            = in_array(Feature::CHARGE_AT_WILL, $features, true);
        $hasSubscriptionsFeature           = in_array(Feature::SUBSCRIPTIONS, $features, true);

        $items = $items->filter(function ($value, $key) use (
            $hasPlTag,
            $hasMarketplaceFeature,
            $hasMarketplaceOrOpenwalletFeature,
            $hasChargeAtWillFeature,
            $hasSubscriptionsFeature)
        {
            switch ($value['type'])
            {
                // Keep invoice type only if payment_link_report is enabled
                case Table::INVOICE:
                    return $hasPlTag;

                // Keep transfer type only if one of marketplace or openwallet is enabled
                case Table::TRANSFER:
                    return $hasMarketplaceOrOpenwalletFeature;

                // Keep reversal type only if marketplace is enabled
                case Table::REVERSAL:
                    return $hasMarketplaceFeature;

                // Show token report to folks with charge_at_will feature only
                case Table::TOKEN:
                    return $hasChargeAtWillFeature;

                case Table::SUBSCRIPTION:
                    return $hasSubscriptionsFeature;

                default:
                    return true;
            }
        });

        return $items;
    }

    protected function filterOnAdminAuth(Merchant\Entity $merchant, $items)
    {
        $isAdmin = $this->ba->isAdminAuth();

        $permissions = ($isAdmin === true) ? $this->ba->getAdmin()->getPermissionsList() : [];

        $hasCommissionPayoutPerm = in_array(Permission::COMMISSION_PAYOUT, $permissions, true);

        $filterConditions = [
            [
                'name'        => 'Unsettled Earnings Report',
                'type'        => 'commissions',
                'report_type' => 'partner',
                'consumer'    => Account::SHARED_ACCOUNT,
                'condition'   => $hasCommissionPayoutPerm,
            ]
        ];

        $items = $items->filter(function ($value) use ($filterConditions) {
            foreach ($filterConditions as $filterCondition)
            {
                if (($value['name'] === $filterCondition['name']) and
                    ($value['type'] === $filterCondition['type']) and
                    ($value['consumer'] === $filterCondition['consumer']))
                {
                    if ((empty($filterCondition['report_type']) === true) or
                        ($filterCondition['report_type'] === $value['report_type']))
                    {
                        return $filterCondition['condition'];
                    }
                }
            }

            return true;
        });

        return $items;
    }

    protected function fetchPartnerReportsControls(Merchant\Entity $merchant)
    {
        $features = $merchant->getEnabledFeatures();

        $hasOfferTag              = in_array(Feature::OFFERS, $features, true);
        $hasGenericNotesTag       = in_array(Feature::REPORTING_GENRERIC_NOTES, $features, true);

        $showTxnCommissionReport          = false;
        $showAggregateCommissionReport    = false;
        $showSubventionReports            = false;
        $showAggregateReports             = false;

        $isAdmin = $this->ba->isAdminAuth();

        if ($merchant->isPartner() === true)
        {
            //
            // show aggregate reports if
            // 1. admin is viewing the reports
            // 2. merchant is not a reseller partner
            //
            $allowReport = (($isAdmin === true) or ($merchant->isResellerPartner() === false));

            if ($allowReport === true)
            {
                $showAggregateReports = true;
            }

            list($commissionConfigs, $subventionConfigs) = (new Config\Core)->fetchAllConfigGroupsByPartner($merchant);

            // if at least one commission config is present, we show commission reports
            if ($commissionConfigs->isNotEmpty() === true)
            {
                if ($allowReport === true)
                {
                    $showTxnCommissionReport        = true;
                    $showAggregateCommissionReport  = true;
                }
                else
                {
                    $showAggregateCommissionReport  = (new Commission\Core)->shouldShowAggregateCommissionReportForPartner($merchant);
                }
            }

            if ($subventionConfigs->isNotEmpty() === true)
            {
                $showSubventionReports = true;
            }
        }

        return [
            'showTxnCommissionReport'       => $showTxnCommissionReport,
            'showAggregateCommissionReport' => $showAggregateCommissionReport,
            'showSubventionReports'         => $showSubventionReports,
            'showAggregateReports'          => $showAggregateReports,
        ];
    }

    protected function filterOnReportTypeAndNameAndConsumer(Merchant\Entity $merchant, $items)
    {
        $features = $merchant->getEnabledFeatures();

        $hasOfferTag              = in_array(Feature::OFFERS, $features, true);
        $hasGenericNotesTag       = in_array(Feature::REPORTING_GENRERIC_NOTES, $features, true);
        $hasNotDisableInstantRefundsTag = !(in_array(Feature::DISABLE_INSTANT_REFUNDS, $features, true));

        $partnerFlags = $this->fetchPartnerReportsControls($merchant);

        $filterConditions = [
            [
                'name'      => 'Offer Payments',
                'type'      => Table::PAYMENT,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $hasOfferTag,
            ],
            [
                'name'      => 'Custom Settlement Recon With Notes',
                'type'      => Table::SETTLEMENT,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $hasGenericNotesTag,
            ],
            [
                'name'      => 'Custom Transfers with Notes',
                'type'      => Table::TRANSFER,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $hasGenericNotesTag,
            ],
            [
                'name'      => 'SubMerchant Report for Platform Partner',
                'type'      => null,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => false,
            ],
            [
                'name'      => 'SubMerchant Report for Non Platform Partner',
                'type'      => null,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => false,
            ],
            [
                'name'      => 'Per Transaction Commission Report',
                'type'      => Table::COMMISSION,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $partnerFlags['showTxnCommissionReport'],
            ],
            [
                'name'      => 'Aggregate Transaction Commission Report',
                'type'      => null,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $partnerFlags['showAggregateCommissionReport'],
            ],
            [
                'name'      => 'Daily Earnings Report',
                'type'      => null,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $partnerFlags['showAggregateCommissionReport'],
            ],
            [
                'name'      => 'Per Transaction Earnings Report',
                'type'      => Table::COMMISSION,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $partnerFlags['showTxnCommissionReport'],
            ],
            [
                'name'      => 'Daily Subvention Report',
                'type'      => null,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $partnerFlags['showSubventionReports'],
            ],
            [
                'name'      => 'Per Transaction Subvention Report',
                'type'      => Table::COMMISSION,
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $partnerFlags['showSubventionReports'],
            ],
            [
                'name'        => 'Payments',
                'type'        => 'payments',
                'report_type' => 'partner',
                'consumer'    => Account::SHARED_ACCOUNT,
                'condition'   => $partnerFlags['showAggregateReports'],
            ],
            [
                'name'        => 'Refunds',
                'type'        => 'refunds',
                'report_type' => 'partner',
                'consumer'    => Account::SHARED_ACCOUNT,
                'condition'   => $partnerFlags['showAggregateReports'],
            ],
            [
                'name'        => 'Combined',
                'type'        => 'transactions',
                'report_type' => 'partner',
                'consumer'    => Account::SHARED_ACCOUNT,
                'condition'   => $partnerFlags['showAggregateReports'],
            ],
            [
                'name'        => 'Settlements',
                'type'        => 'settlements',
                'report_type' => 'partner',
                'consumer'    => Account::SHARED_ACCOUNT,
                'condition'   => $partnerFlags['showAggregateReports'],
            ],
            [
                'name'        => 'Settlements Recon',
                'type'        => 'settlements',
                'report_type' => 'partner',
                'consumer'    => Account::SHARED_ACCOUNT,
                'condition'   => $partnerFlags['showAggregateReports'],
            ],
            [
                'name'      => 'Instant Refunds',
                'type'      => 'refunds',
                'consumer'  => Account::SHARED_ACCOUNT,
                'condition' => $hasNotDisableInstantRefundsTag,
            ],
        ];

        $items = $items->filter(function ($value) use ($filterConditions) {
            foreach ($filterConditions as $filterCondition)
            {
                if (($value['name'] === $filterCondition['name']) and
                    ($value['type'] === $filterCondition['type']) and
                    ($value['consumer'] === $filterCondition['consumer']))
                {
                    if ((empty($filterCondition['report_type']) === true) or
                        ($filterCondition['report_type'] === $value['report_type']))
                    {
                        return $filterCondition['condition'];
                    }
                }
            }

            return true;
        });

        return $items;
    }

    protected function filterForBusinessBanking(Merchant\Entity $merchant, $items)
    {
        // We don't use Business Banking attribute of merchant because we don't want
        // to show these reports to the merchant on the PG dashboard. Hence, we use the
        // header to figure out whether the request is coming from RX dashboard.
        $isBusinessBanking = $this->ba->isProductBanking();

        $isAdminAuth = $this->ba->isAdminAuth();

        $items = $items->filter(
            function($value) use ($isBusinessBanking, $isAdminAuth)
            {
                $isBusinessBankingReport = (starts_with(strtolower($value['name']), 'rx') === true);
                //
                // We want to expose all configs on admin auth
                // This has been done, so that banking configs
                // show up on self serve dashboard
                //
                if ($isAdminAuth === true)
                {
                    return true;
                }

                // If business banking, return only business banking reports.
                // If not business banking, return all reports except business banking reports.

                if ($isBusinessBanking === true)
                {
                    return ($isBusinessBankingReport === true);
                }
                else
                {
                    return ($isBusinessBankingReport === false);
                }
            });

        return $items;
    }

    protected function traceReportingServiceRequest(array $request)
    {
        $this->trace->info(
            TraceCode::REPORTING_SERVICE_API_REQUEST,
            $this->getTraceableRequest($request));
    }

    protected function traceReportingServiceResponse($response)
    {
        $payload = ['status_code' => $response->status_code, 'body' => null];

        // Trace body only if response is non 200
        if ($response->status_code !== 200)
        {
            $payload['body'] = $response->body;
        }

        $this->trace->info(TraceCode::REPORTING_SERVICE_API_RESPONSE, $payload);
    }

    protected function validateResponse($response)
    {
        if ($response->status_code !== 200)
        {
            $responseBody = json_decode($response->body, true);
            $payload = [
                'body' => $responseBody,
            ];
            $errorMsg = null;

            if (isset($responseBody['error']) and
                isset($responseBody['error']['description']))
            {
                $errorMsg = $responseBody['error']['description'];
            }

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REPORTING_INTEGRATION, null, $payload, $errorMsg);
        }
    }

    protected function validateMerchantReportType($reportType)
    {
        if ((empty($reportType) === false))
        {
            if (($reportType !== self::MERCHANT) and
                ($reportType !== self::PARTNER) and
                ($reportType !== self::RAZORPAYX))
            {
                throw new Exception\BadRequestValidationFailureException('Invalid report type');
            }

            $merchant = $this->ba->getMerchant();

            if ((empty($merchant) === false) and ($reportType === self::PARTNER) and ($merchant->isPartner() === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER);
            }
        }
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param  array  $request
     *
     * @return array
     */
    protected function getTraceableRequest(array $request): array
    {
        return array_only($request, ['url', 'method', 'content', 'headers']);
    }

    /**
     * Fetches linked account parent id from exisiting ba account context.
     */
    protected function getLinkedAccountParentId()
    {
        $merchant = $this->ba->getMerchant();

        $parentId = null;

        if ((empty($merchant) === false) and ($merchant->isMarketplace() === true))
        {
            $parentId = $merchant->getId();
        }
        else if ((empty($merchant) === false) and ($merchant->isLinkedAccount() === true))
        {
            $parentId = $merchant->parent->getId();
        }

        return $parentId;
    }

    /**
     * Loop through all the reporting schedules and link API schedule object in it.
     *
     * @param  array  $scheduleData
     *
     * @return array
     */
    protected function linkAllScheduledTasks(array $scheduleDataList)
    {

        if (isset($scheduleDataList['error']) === true)
        {
            return $scheduleDataList;
        }

        if (isset($scheduleDataList['items']) === false)
        {
            return $scheduleDataList;
        }

        foreach ($scheduleDataList['items'] as &$item)
        {
            $item = $this->linkSingleScheduledTasks($item);
        }

        return $scheduleDataList;
    }

    /**
     * Linking API schedule object in the passed reporting schedule object so the frontend can show the
     * schedule details to the users
     *
     * @param  array  $scheduleData
     *
     * @return array
     */
    protected function linkSingleScheduledTasks(array $scheduleData)
    {

        if (isset($scheduleData['id']) === false)
        {
            return $scheduleData;
        }

        $entityId = $this->generateEntityId($scheduleData['id']);

        $scheduleTask = $this->repo->schedule_task->fetchByEntity($entityId);

        if ($scheduleTask !== null)
        {
            $scheduleData['schedule'] = $scheduleTask->schedule;
        }

        return $scheduleData;
    }

    private function validateRequestFromOrg(?string $consumer)
    {
        //
        // The below if condition check is not for admins of RZP organisation. Here, Admin from other org
        // should only access data for their own org(consumer as org_id) or else consumer should be empty.
        // Admin auth will be used here.
        //
        $orgId = $this->ba->getAdmin()->getOrgId();

        if (($orgId !== Org\Entity::RAZORPAY_ORG_ID) and
            (empty($consumer) === false and $orgId !== $consumer))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REPORTING_OTHER_ORG_INVALID_REQUEST);
        }
    }


    private function failIfAdminTryingToDownloadMerchantReports(?string $reportType)
    {
        // For non-merchant reports X_REPORT_TYPE should not be MERCHANT
        // otherwise admins/banks will be able to download merchant reports.
        // Admin auth will be used here
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Allow Razorpay Admin to access routes in RZP_ADMIN_ROUTES_WITH_MERCHANT_REPORT_TYPE for
        // configuring org reports and allow X_REPORT_TYPE as MERCHANT_REPORT_TYPES.
        // These routes are used for configuration and not for downloading.
        if ($this->ba->getAdmin()->getOrgId() === Org\Entity::RAZORPAY_ORG_ID and
            (in_array($routeName, self::RZP_ADMIN_ROUTES_WITH_MERCHANT_REPORT_TYPE, true) === true))
        {
            return;
        }

        // allowing only routes in this ADMIN_ROUTES_WITH_MERCHANT_REPORT_TYPE
        // to contain X_REPORT_TYPE as MERCHANT_REPORT_TYPES
        if (in_array($routeName, self::ADMIN_ROUTES_WITH_MERCHANT_REPORT_TYPE, true) === false)
        {
            if (in_array($reportType, self::MERCHANT_REPORT_TYPES, true) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REPORTING_ADMIN_NOT_ALLOWED_MERCHANT_REPORTS);
            }
        }

    }

    // buildCaTransactionRawQueryParams is required to transform the config filters format
    // into raw sql config supported format. This is required as to handle CA transactions
    // we need raw sql config, hence need arises to convert the format of data send by dasboard.

    private function buildCaTransactionRawQueryParams(array $input)
    {
        $filters = $input[self::TEMPLATE_OVERRIDES][self::FILTERS];

        unset($input[self::TEMPLATE_OVERRIDES][self::FILTERS]);

        $query_params = [];

        // traversing over the filters and building raw query params
        // using them for custom query

        foreach ($filters as $filterKey => $filterValue)
        {
            if ($filterKey === Constants::BALANCE)
            {
                $balanceFilters = $filters[$filterKey];

                foreach ($balanceFilters as $key => $value)
                {
                    if ($key === Constants::ACCOUNT_NUMBER)
                    {
                        $query_params[Constants::ACCOUNT_NUMBER] = $value['values'][0];
                    }
                    else
                    {
                        $this->trace->error(TraceCode::REPORTING_SERVICE_INVALID_PARAMS_CA_TRANSACTION, $balanceFilters);
                    }
                }
            }

            if ($filterKey === Constants::CONTACTS)
            {
                $contactsFilters = $filters[$filterKey];

                foreach ($contactsFilters as $key => $value)
                {
                    if ($key === Constants::CONTACT)
                    {
                        $query_params[Constants::CONTACTS_CONTACT] = $value['values'][0];
                    }
                    else if ($key === Constants::EMAIL)
                    {
                        $query_params[Constants::CONTACTS_EMAIL] = $value['values'][0];
                    }
                    else if ($key === Constants::ID)
                    {
                        $query_params[Constants::CONTACTS_ID] = $value['values'][0];
                    }
                    else if ($key === Constants::TYPE)
                    {
                        $query_params[Constants::CONTACTS_TYPE] = $value['values'][0];
                    }
                    else
                    {
                        $this->trace->error(TraceCode::REPORTING_SERVICE_INVALID_PARAMS_CA_TRANSACTION, $contactsFilters);
                    }
                }
            }

            if ($filterKey === Constants::PAYOUTS)
            {
                $payoutsFilters = $filters[$filterKey];

                foreach ($payoutsFilters as $key => $value)
                {
                    if ($key === Constants::ID)
                    {
                        $query_params[Constants::PAYOUTS_ID] = $value['values'][0];
                    }
                    else if ($key === Constants::PURPOSE)
                    {
                        $query_params[Constants::PAYOUTS_PURPOSE] = $value['values'][0];
                    }
                    else if ($key === Constants::MODE)
                    {
                        $query_params[Constants::PAYOUTS_MODE] = $value['values'][0];
                    }
                    else
                    {
                        $this->trace->error(TraceCode::REPORTING_SERVICE_INVALID_PARAMS_CA_TRANSACTION, $payoutsFilters);
                    }
                }
            }

            if ($filterKey === Constants::TRANSACTIONS)
            {
                $transactionsFilters = $filters[$filterKey];

                foreach ($transactionsFilters as $key => $value)
                {
                    if ($key === Constants::ID)
                    {
                        $query_params[Constants::TRANSACTIONS_ID] = $value['values'][0];
                    }
                    else if ($key === Constants::TYPE)
                    {
                        $query_params[Constants::TRANSACTIONS_TYPE] = $value['values'][0];
                    }
                    else if ($key === Constants::CREDIT)
                    {
                        $query_params[Constants::TRANSACTIONS_CREDIT] = $value['values'][0];
                    }
                    else if ($key === Constants::DEBIT)
                    {
                        $query_params[constants::TRANSACTIONS_DEBIT] = $value['values'][0];
                    }
                    else
                    {
                        $this->trace->error(TraceCode::REPORTING_SERVICE_INVALID_PARAMS_CA_TRANSACTION, $transactionsFilters);
                    }
                }
            }

            if ($filterKey === Constants::FUND_ACCOUNTS)
            {
                $fundAccountsFilters = $filters[$filterKey];

                foreach ($fundAccountsFilters as $key => $value)
                {
                    if ($key === Constants::ID)
                    {
                        $query_params[Constants::FUND_ACCOUNTS_ID] = $value['values'][0];
                    }
                    else
                    {
                        $this->trace->error(TraceCode::REPORTING_SERVICE_INVALID_PARAMS_CA_TRANSACTION, $fundAccountsFilters);
                    }
                }
            }
        }

        $input[self::TEMPLATE_OVERRIDES][self::RAW_SQL][self::QUERY_PARAMS] = $query_params;

        return $input;
    }

    protected function validateInput(array $input)
    {
        $validator = ValidationFactory::getReportTypeBasedValidator(
            $this->headers[self::REPORT_TYPE_HEADER] ?? '',
            $input);

        if ($validator !== null) {
            $validator->validate();
        }
    }

    protected function validateFeatures(array $input)
    {
        if (array_key_exists(Feature::FEATURE_NAMES, $input) &&
        empty($input[Feature::FEATURE_NAMES] === false))
        {
            $featureNames = $input[Feature::FEATURE_NAMES];
            (new FeatureService())->validateFeatureNames($featureNames);
        }
    }

    protected function addAssociatedFeaturesHeader()
    {
        $merchant = $this->ba->getMerchant();

        if (empty($merchant) === false)
        {
            $enabledFeatures = $merchant->getEnabledFeatures();

            $this->headers[self::ASSOCIATED_FEATURES_HEADER] = json_encode($enabledFeatures);
        }
    }

    /**
     * Verifies if Admin is performing report configuration for an OrgId
     *
     * @throws Exception\BadRequestException if orgId passed in X-Consumer header is invalid
     * @throws Exception\BadRequestValidationFailureException if X-Consumer header is missing
     */
    private function verifyIfAdminPerformingOrgReportConfig()
    {
        $consumer = Request::header(self::CONSUMER_HEADER);

        if (empty($consumer) === false)
        {
            try
            {
                $this->repo->org->isValidOrg(Org\Entity::verifyIdAndSilentlyStripSign($consumer));

                return;
            }
            catch (Exception\BadRequestException $e)
            {
                $errorMsg = "Admin auth should not be used for configuring non-org reports";
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED, null, null, $errorMsg);
            }
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MISSING_HEADERS);
        }
    }

    /**
     * Adds org dashboard hostname in the Template Overrides of the Log.
     * This is used in Reporting Service for sending report download url over email.
     *
     * @param array $input
     */
    private function addOrgHostname(array & $input)
    {
        $input[self::TEMPLATE_OVERRIDES] = ((isset($input[self::TEMPLATE_OVERRIDES]) === true) and
            (is_array($input[self::TEMPLATE_OVERRIDES]) === true)) ?  $input[self::TEMPLATE_OVERRIDES]: [];

        $orgHostName = $this->ba->getOrgHostName();

        if (empty($orgHostName) === false)
        {
            $input[self::TEMPLATE_OVERRIDES][self::DASHBOARD_HOST_NAME] = $orgHostName;
        }
    }
}
