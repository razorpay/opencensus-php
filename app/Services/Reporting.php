<?php

namespace RZP\Services;

use App;
use Requests;
use Requests_Response;
use Requests_Exception;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Schedule\Task as ScheduleTask;

/**
 * Interface for api to talk to Reporting service
 */
class Reporting
{
    const REQUEST_TIMEOUT = 30; // In secs

    /**
     * Path for various endpoints
     */
    const CONFIG_PATH   = '/v1/configs';
    const LOG_PATH      = '/v1/logs';
    const SCHEDULE_PATH = '/v1/schedules';

    const SCHEDULE_PREFIX = 'sched_';

    /**
     * @var array
     */
    protected $config;

    protected $trace;

    protected $mode;

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
    }

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

        return $this->createAndSendRequest(Requests::DELETE, $path);
    }

    public function createLog(array $input): array
    {
        //
        // Adds mode to create log input. Mode is only relavent in this endpoint
        // (creating log) as log entity's mode attribute is used to query
        // respective database of api. Config is mode independent in reporting service.
        //
        $input['mode'] = $this->mode;

        return $this->createAndSendRequest(Requests::POST, self::LOG_PATH, $input);
    }

    public function fetchLogById(string $id): array
    {
        $path = self::LOG_PATH . '/' . $id;

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

            $this->createScheduleOnAPI($scheduleRequest);
        }

        return $response;
    }

    public function fetchScheduleMultiple(array $input): array
    {
        $configs = $this->createAndSendRequest(Requests::GET, self::SCHEDULE_PATH, $input);

        return $this->filterConfigsByFeatureAndTags($configs);
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
            $entityId = $this->generateEntityId($id);

            $scheduleTask = $this->repo->schedule_task->fetchByEntity($entityId);

            $this->repo->deleteOrFail($scheduleTask);
        }

        return $response;
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

                $scheduleTask->updateNextRunAndLastRun(false);

                $this->repo->saveOrFail($scheduleTask);
            }
        }

        return $response;
    }

    protected function createScheduleOnAPI(array $input)
    {
        $entityId = $input[ScheduleTask\Entity::ENTITY_ID];

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
                'id'          => self::SCHEDULE_PREFIX . $scheduleTask->getEntityId(),
                'merchant_id' => $scheduleTask->getMerchantId(),
            ];
        }

        // Mode is necessary to trigger a schedule
        // Depending upon mode, the corresponding test/live data would be fetched
        $request = [
            'mode'    => $this->mode,
            'payload' => $payload,
        ];

        $path = self::SCHEDULE_PATH . '/trigger';

        return $this->createAndSendRequest(Requests::POST, $path, $request, Merchant\Account::SHARED_ACCOUNT);
    }

    protected function generateEntityId(string $entityId)
    {
        return explode(self::SCHEDULE_PREFIX, $entityId)[1];
    }

    protected function createAndSendRequest(
        string $method,
        string $path,
        array $input = [],
        string $merchantId = null): array
    {
        // In case reporting is to be mocked, don't make any external call
        // and just return empty array.
        if ($this->config['mock'] === true)
        {
            return [];
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $headers = [
            'X-Merchant-Id' => $merchantId ?? $this->ba->getMerchantId()
        ];

        $request = [
            'url'     => $this->config['url'] . $path,
            'method'  => $method,
            'content' => $input,
            'options' => $options,
            'headers' => $headers
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
            return Requests::request(
                        $request['url'],
                        $request['headers'],
                        $request['content'],
                        $request['method'],
                        $request['options']);
        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REPORTING_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            throw new Exception\IntegrationException('
                Could not recieve proper response from reporting service');
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
            $this->config['auth']['username'],
            $this->config['auth']['password'],
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
        $tags     = array_map('strtolower', $merchant->tagNames());
        $features = $merchant->getEnabledFeatures();

        $hasPlTag                      = in_array('payment_link_report', $tags, true);
        $hasMarketplaceTag             = in_array(Feature::MARKETPLACE, $features, true);
        $hasOpenwalletTag              = in_array(Feature::OPENWALLET, $features, true);
        $hasMarketplaceOrOpenwalletTag = ($hasMarketplaceTag or $hasOpenwalletTag);

        $items = $items->filter(function ($value, $key) use (
            $hasPlTag,
            $hasMarketplaceTag,
            $hasMarketplaceOrOpenwalletTag)
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

                default:
                    return true;
            }
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
}
