<?php

namespace RZP\Services;

use App;
use Request;
use Requests;
use Requests_Response;
use Requests_Exception;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Base\Common;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Schedule\Task as ScheduleTask;

/**
 * Interface for api to talk to Reporting service
 */
class Reporting implements ExternalService
{
    const REQUEST_TIMEOUT = 30; // In secs

    /**
     * Path for various endpoints
     */
    const CONFIG_PATH           = '/v1/configs';
    const LOG_PATH              = '/v1/logs';
    const ADMIN_LOG_PATH        = '/v1/admin-logs';
    const SCHEDULE_PATH         = '/v1/schedules';

    const SCHEDULE_PREFIX = 'sched_';

    const LOGS          = 'logs';
    const CONFIGS       = 'configs';
    const SCHEDULES     = 'schedules';

    // REPORT_TYPE constants
    const MERCHANT      = 'merchant';

    // Headers
    const CONSUMER_HEADER       = 'X-Consumer';
    const REPORT_TYPE_HEADER    = 'X-Report-Type';
    const ADMIN_TOKEN_HEADER    = 'X-Admin-Token';
    const LINKED_ACCOUNT_HEADER = 'X-Linked-Account-Parent';

    /**
     * @var array
     */
    protected $config;

    protected $trace;

    protected $mode;

    protected $headers;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    public function __construct()
    {
        $app = App::getFacadeRoot();

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

            if ($merchantId === Account::SHARED_ACCOUNT)
            {
                // If SHARED_ACCOUNT, use headers sent
                // Useful for creating schedules for non-merchants
                $headers[self::REPORT_TYPE_HEADER] = $reportType ?: self::MERCHANT;
                $headers[self::CONSUMER_HEADER] = $consumer ?: Account::SHARED_ACCOUNT;
            }
            else
            {
                // MERCHANT Reports
                // Proxy Auth used here
                $headers[self::REPORT_TYPE_HEADER] = self::MERCHANT;
                $headers[self::CONSUMER_HEADER] = $merchantId;
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

            // Entity view in dashboard
            $headers[self::ADMIN_TOKEN_HEADER] = $adminToken;

            // For non-merchant reports X_REPORT_TYPE should not be MERCHANT
            // otherwise admins/banks will be able to download merchant reports.
            // Admin auth will be used here
            if ($reportType === self::MERCHANT)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REPORTING_INTEGRATION);
            }

            if ((empty($reportType) === false) and
                (empty($consumer) === false))
            {
                $headers[self::REPORT_TYPE_HEADER] = $reportType;
                $headers[self::CONSUMER_HEADER] = $consumer;
            }
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
        return $this->createAndSendRequest(Requests::POST, self::CONFIG_PATH, $input);
    }

    public function fetchConfigMultiple(array $input): array
    {
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

    public function createLog(array $input): array
    {
        //
        // Adds mode to create log input. Mode is only relavent in this endpoint
        // (creating log) as log entity's mode attribute is used to query
        // respective database of api. Config is mode independent in reporting service.
        //
        $input['mode'] = $this->mode;

        $path = self::LOG_PATH;

        if (empty($input['emails']) === true)
        {
            $path .= '?send_email=false';
        }

        return $this->createAndSendRequest(Requests::POST, $path, $input);
    }

    public function editLog(string $id, array $input): array
    {
        $path = self::LOG_PATH . '/' . $id;

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
        return $this->createAndSendRequest(Requests::GET, self::LOG_PATH, $input);
    }

    public function createSchedule(array $input): array
    {
        $reportingServiceRequest = $input['payload'];

        $scheduleRequest = $input['schedule'];

        $response = $this->createScheduleOnReportingService($reportingServiceRequest);

        // In case reporting service returns error, then we dont create schedule/schedule task
        if (isset($response['error']) === false)
        {
            $this->trace->info(TraceCode::REPORTING_SERVICE_CREATE_SCHEDULE, $input);

            // Need to store entity_id without sign.
            $scheduleRequest[ScheduleTask\Entity::ENTITY_ID] = $this->generateEntityId($response['id']);

            $this->createScheduleOnApi($scheduleRequest);
        }

        return $response;
    }

    public function fetchScheduleMultiple(array $input): array
    {
        return $this->createAndSendRequest(Requests::GET, self::SCHEDULE_PATH, $input);
    }

    public function fetchScheduleById(string $id): array
    {
        $path = self::SCHEDULE_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function deleteSchedule(string $id): array
    {
        $path = self::SCHEDULE_PATH . '/' . $id;

        $response = $this->createAndSendRequest(Requests::DELETE, $path);

        // Deleting the corresponding schedule task as well.
        if (isset($response['error']) === false)
        {
            $this->deleteScheduleTask($id);
        }

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
        return $this->createAndSendRequest(Requests::GET, self::LOG_PATH, $input);
    }

    // TODO: Add filter based upon feature/tags for admin calls
    public function fetchConfigMultipleAdmin(array $input): array
    {
        return $this->createAndSendRequest(Requests::GET, self::CONFIG_PATH, $input);
    }

    public function fetchScheduleMultipleAdmin(array $input): array
    {
        return $this->createAndSendRequest(Requests::GET, self::SCHEDULE_PATH, $input);
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

        (new ScheduleTask\Core)->createForExternalService($merchant, $scheduleTaskRequest);
    }

    protected function createScheduleOnReportingService(array $input): array
    {
        $response = $this->createAndSendRequest(Requests::POST, self::SCHEDULE_PATH, $input);

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
            return [];
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

    protected function sendRequest(array $request): Requests_Response
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

            return $response;

        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REPORTING_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

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

        $merchant = $this->ba->getMerchant();

        // Don't filter anything for non merchants
        if (empty($merchant) === true)
        {
            return $configs;
        }

        $tags     = array_map('strtolower', $merchant->tagNames());
        $features = $merchant->getEnabledFeatures();

        $hasPlTag                      = in_array('payment_link_report', $tags, true);
        $hasMarketplaceTag             = in_array(Feature::MARKETPLACE, $features, true);
        $hasOpenwalletTag              = in_array(Feature::OPENWALLET, $features, true);
        $hasMarketplaceOrOpenwalletTag = ($hasMarketplaceTag or $hasOpenwalletTag);
        $hasOfferTag                   = in_array(Feature::OFFERS, $features, true);
        $hasChargeAtWillTag            = in_array(Feature::CHARGE_AT_WILL, $features, true);
        $hasSubscriptionsTag           = in_array(Feature::SUBSCRIPTIONS, $features, true);

        $items = $items->filter(function ($value, $key) use (
            $hasPlTag,
            $hasMarketplaceTag,
            $hasMarketplaceOrOpenwalletTag,
            $hasChargeAtWillTag,
            $hasSubscriptionsTag)
        {
            switch ($value['type'])
            {
                // Keep invoice type only if payment_link_report is enabled
                case Table::INVOICE:
                    return $hasPlTag;

                // Keep transfer type only if one of marketplace or openwallet is enabled
                case Table::TRANSFER:
                    return $hasMarketplaceOrOpenwalletTag;

                // Keep reversal type only if marketplace is enabled
                case Table::REVERSAL:
                    return $hasMarketplaceTag;

                // Show token report to folks with charge_at_will feature only
                case Table::TOKEN:
                    return $hasChargeAtWillTag;

                case Table::SUBSCRIPTION:
                    return $hasSubscriptionsTag;

                default:
                    return true;
            }
        });

        $items = $items->filter(function ($value) use ($hasOfferTag)
        {
            return (($value['name'] === 'Offer Payments') and
                ($value['type'] === Table::PAYMENT) and
                ($value['consumer'] === Account::SHARED_ACCOUNT)) ? $hasOfferTag : true;
        });

        $configs['items'] = $items->values()->all();
        $configs['count'] = $items->count();

        return $configs;
    }

    protected function traceReportingServiceRequest(array $request)
    {
        $this->trace->info(
            TraceCode::REPORTING_SERVICE_API_REQUEST,
            $this->getTraceableRequest($request));
    }

    protected function traceReportingServiceResponse(Requests_Response $response)
    {
        $payload = ['status_code' => $response->status_code, 'body' => null];

        // Trace body only if response is non 200
        if ($response->status_code !== 200)
        {
            $payload['body'] = $response->body;
        }

        $this->trace->info(TraceCode::REPORTING_SERVICE_API_RESPONSE, $payload);
    }

    protected function validateResponse(Requests_Response $response)
    {
        if ($response->status_code !== 200)
        {
            $payload['body'] = $response->body;

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REPORTING_INTEGRATION, null, $payload);
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
}
