<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use App\Http\AppResponse;
use RZP\Exception\BadRequestException;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Reminders\ReminderProcessor;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;

class RemindersController extends Controller
{
    protected $reminders;

    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    const WHITELISTED_ROUTES_REGEX = [
        self::GET => [
            '^batch$',
            '^merchant_config$',
            '^merchant_config\/[[:alnum:]]{14}$',
            '^merchant_settings$',
            '^merchant_settings\/[[:alnum:]]{14}$',
            '^configs$',
            '^configs\/[[:alnum:]]{14}$',
            '^batch\/service\/control$',
            '^circuit_config$',
            '^reminders_experiment\/[[:alnum:]]{100}$',
            '^callback_url\/[[:alnum]]{50}$',
        ],
        self::POST => [
            '^batch$',
            '^merchant_config$',
            '^merchant_settings$',
            '^configs$',
            '^batch\/service\/control$',
            '^service\/[[:alnum:]|_]{1,100}\/configure$',
            '^circuit_config$',
            '^reminders_experiment$',
            '^callback_url$',
        ],
        self::PUT => [
            '^merchant_config$',
            '^service\/[[:alnum:]|_]{1,100}\/configure$',
            '^circuit_config$',
            '^reminders_experiment$',
        ],
        self::PATCH => [
            '^merchant_settings\/[[:alnum:]]{14}$',
            '^configs\/namespace\/[[:alnum:]|_]{1,100}$',
            '^callback_url$',
        ],
        self::DELETE => [
            '^configs\/[[:alnum:]]{14}$',
        ]
    ];

    const WHITELIST_ADMIN_ROUTES_REGEX = [
        self::GET => [
            '^batch$',
            '^merchant_settings$',
            '^merchant_settings\/[[:alnum:]]{14}$',
            '^configs$',
            '^configs\/[[:alnum:]]{14}$',
            '^batch\/service\/control$',
            '^circuit_config$',
            '^reminders_experiment\/[[:alnum:]]{100}$',
            '^callback_url\/[[:alnum]]{50}$',
        ],
        self::POST => [
            '^batch$',
            '^merchant_config$',
            '^merchant_settings$',
            '^configs$',
            '^namespace\/[[:alnum:]|_]{1,100}\/control$',
            '^batch\/service\/control$',
            '^service\/[[:alnum:]|_]{1,100}\/configure$',
            '^circuit_config$',
            '^reminders_experiment$',
            '^callback_url$',
        ],
        self::PUT => [
            '^configs\/[[:alnum:]]{14}$',
            '^service\/[[:alnum:]|_]{1,100}\/configure$',
            '^circuit_config$',
            '^reminders_experiment$',
        ],
        self::PATCH => [
            '^merchant_settings\/[[:alnum:]]{14}$',
            '^reminders\/next_run_at\/[[:alnum:]]{14}$',
            '^configs\/namespace\/[[:alnum:]|_]{1,100}$',
            '^callback_url$',
        ],
        self::DELETE => [
            '^configs\/[[:alnum:]]{14}$',
        ]
    ];

    protected $paymentlinkservice;

    public function __construct()
    {
        parent::__construct();

        $this->reminders = $this->app['reminders'];

        $this->paymentlinkservice = $this->app['paymentlinkservice'];
    }

    public function sendReminder(string $mode, string $entity, string $namespace, string $id)
    {
        $input = Request::all();

        $mode = ($mode === Mode::TEST) ? Mode::TEST : Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $processor = ReminderProcessor::getReminderProcessorName($namespace);

        $response = (new $processor)->process($entity, $namespace, $id, $input);

        return ApiResponse::json($response);
    }

    public function handleAny($path = '')
    {
        $method = Request::method();

        if(array_key_exists($method, self::WHITELISTED_ROUTES_REGEX) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedRoutesRegex = implode('|', self::WHITELISTED_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedRoutesRegex . '/', $path, $pathMatches) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $method = Request::method();

        $data = Request::all();

        $response = $this->reminders->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        $this->sendSelfServeSuccessAnalyticsEventToSegmentForEnablingReminders($path, $pathMatches, $data, $statusCode);

        return ApiResponse::json($response, $statusCode);
    }

    public function handleDisableVerifySpinnaker() {
        $method = Request::method();
        $data = Request::all();

        if (isset($data['active']) === true) {
            $data['active'] = (bool) $data['active'];
        }

        $response = $this->reminders->sendAnyRequest('batch/service/control', $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function remindersAdmin($path = '')
    {
        $method = Request::method();

        if(array_key_exists($method, self::WHITELIST_ADMIN_ROUTES_REGEX) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedAdminRoutesRegex = implode('|', self::WHITELIST_ADMIN_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedAdminRoutesRegex . '/', $path, $pathMatches) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $method = Request::method();
        $data = Request::all();

        $response = $this->reminders->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function remindersNextRun(string $entity, string $id, string $namespace = '')
    {
        if ($this->shouldForwardToPaymentLinkService($entity) === true)
        {
            try {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
            } catch (\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
                // do nothing. will try fetching from invoice repo
            }
        }

        $processor = ReminderProcessor::getReminderProcessorName($namespace);

        $response =  (new $processor)->nextRunAt($entity, $id);

        return ApiResponse::json($response);
    }

    protected function shouldForwardToPaymentLinkService(string $entity): bool
    {
        if ($entity !== Entity::INVOICE)
        {
           return false;
        }

        if ($this->app['basicauth']->isPaymentLinkServiceApp() === true)
        {
            return false;
        }

        $merchant = $this->app['basicauth']->getMerchant();

        if ($merchant !== null)
        {
            if ($merchant->isFeatureEnabled(Feature::PAYMENTLINKS_COMPATIBILITY_V2) === false)
            {
                return false;
            }

            return true;
        }

        return false;
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForEnablingReminders($path, $pathMatches, $data, $statusCode)
    {
        if ((preg_match('/^merchant_settings\/[[:alnum:]]{14}$/', $path, $pathMatches) == true) and
            (isset($data['active']) === true) and
            ($data['active'] === true) and
            ($statusCode === 200))
        {
            $segmentProperties = [];

            $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

            $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

            $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

            $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'PL Reminder Created';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $this->ba->getMerchant(), $segmentProperties, $segmentEventName
            );
        }
    }
}
