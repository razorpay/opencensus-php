<?php

namespace RZP\Services;

use Throwable;
use \WpOrg\Requests\Session as Requests_Session;
use \WpOrg\Requests\Response;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\ServerErrorException;

class WorkflowService
{
    // Request timeout in milliseconds for all HTTP requests to workflow service
    const REQUEST_TIMEOUT = 350;
    // Request connect timeout in milliseconds for all HTTP requests to workflow service
    // Request timeout parameter applies after connection is established.
    const REQUEST_CONNECT_TIMEOUT = 350;

    const EMAIL_HEADER          = 'X-User-Email';
    const CONTENT_TYPE_HEADER   = 'Content-Type';

    const WORKFLOW_SERVICE_REQUEST_MILLISECONDS = "WORKFLOW_SERVICE_REQUEST_MILLISECONDS";

    // Metrics
    const WORKFLOW_SERVICE_REQUEST_FAILURE      = "WORKFLOW_SERVICE_REQUEST_FAILURE";
    const WORKFLOW_SERVICE_REQUEST_SUCCESS      = "WORKFLOW_SERVICE_REQUEST_SUCCESS";
    const WORKFLOW_SERVICE_REQUEST_RETRY        = "WORKFLOW_SERVICE_REQUEST_RETRY";

    /** @var $request Requests_Session */
    public $request;

    /** @var Trace */
    protected $trace;

    protected $app;

    /** @var $ba BasicAuth */
    protected $ba;

    protected $config;

    public function __construct($app)
    {
        $this->app          = $app;
        $this->ba           = $app['basicauth'];
        $this->trace        = $app['trace'];
        $this->config       = $app['config'];
    }

    protected function init()
    {
        $auth = [
            $this->config->get('applications.workflows.username'),
            $this->config->get('applications.workflows.password')
        ];

        // Options and authentication for requests.
        $options = [
            'auth'  => $auth,
        ];

        $this->request = new Requests_Session(
            $this->config->get('applications.workflows.url'),
            // Common headers for requests.
            [
                self::CONTENT_TYPE_HEADER           => 'application/json',
                self::EMAIL_HEADER                  => $this->getActorEmail(),
                'timeout'                           => self::REQUEST_TIMEOUT,
                'connect_timeout'                   => self::REQUEST_CONNECT_TIMEOUT,
            ],
            [],
            $options
        );
    }

    /**
     * @param string $path
     * @param array $payload
     * @return \WpOrg\Requests\Response|null
     * @throws ServerErrorException
     */
    public function request(string $path, array $payload)
    {
        $this->init();
        $res = null;
        $exception = null;
        $maxAttempts = 2;

        while ($maxAttempts--)
        {
            try
            {
                $this->trace->info(TraceCode::WORKFLOW_SERVICE_REQUEST_DETAILS, [
                    'path'      => $path,
                    'payload'   => $payload,
                ]);

                $startAt = millitime();

                $res = $this->request->post($path, [], empty($payload) ? '{}' : json_encode($payload));

                $this->trace->histogram(
                    self::WORKFLOW_SERVICE_REQUEST_MILLISECONDS,
                    millitime() - $startAt);
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e,
                    Trace::CRITICAL,
                    TraceCode::SERVER_ERROR_WORKFLOW_SERVICE_ERROR,
                    ['payload' => $payload]);

                if ($maxAttempts > 0)
                {
                    $this->trace->count(self::WORKFLOW_SERVICE_REQUEST_RETRY);
                }

                $exception = $e;
                continue;
            }

            // In case it succeeds in another attempt.
            $exception = null;
            break;
        }

        if (($exception !== null) or
            ($res->success !== true))
        {
            $responseInfo = empty($res) === false
                ? ['resp_status_code' => $res->status_code, 'resp_body' => $res->body]
                : [];

            $exceptionInfo = empty($exception) === false ? $exception->getMessage() : "";

            $responseInfo += ['req_path' => $path, 'message' => $exceptionInfo, 'payload' => $payload];

            $this->trace->error(
                TraceCode::SERVER_ERROR_WORKFLOW_SERVICE_ERROR,
                $responseInfo);

            $this->trace->count(self::WORKFLOW_SERVICE_REQUEST_FAILURE);

            // Return the response received from workflow service.
            // Take care of throwing appropriate response in the service layer.
            // Make this default, and remove throwing default ServerErrorException
            $isSSWFEnabled = false;

            $isAdminAuth = $this->app['basicauth']->isAdminAuth();
            $isInternalApp = $this->app['basicauth']->isInternalApp();

            if ($isAdminAuth === false && $isInternalApp === false)
            {
                $isSSWFEnabled = $this->app['razorx']->getTreatment($this->ba->getMerchantId(),
                        Merchant\RazorxTreatment::RX_SELF_SERVE_WORKFLOW,
                        Mode::LIVE) === 'on';
            }

            if ($isAdminAuth == true || $isSSWFEnabled === true || $isInternalApp === true)
            {
                return $res;
            }
            else
            {
                throw new ServerErrorException(
                    "Failed to complete request",
                    ErrorCode::SERVER_ERROR_WORKFLOW_SERVICE_ERROR,
                    $responseInfo,
                    $exception);
            }
        }

        $this->trace->count(self::WORKFLOW_SERVICE_REQUEST_SUCCESS);

        $this->trace->info(TraceCode::WORKFLOW_SERVICE_RESPONSE_DETAILS, [
            'path'      => $path,
            'status'    => $res->status_code,
            'content'   => $res->body
        ]);

        return $res;
    }

    private function getActorEmail()
    {
        $user = $this->ba->getUser();

        if (empty($user) === true)
        {
            if ($this->ba->isAdminAuth() and empty($this->ba->getAdmin()) === false)
            {
                return $this->ba->getAdmin()->getEmail();
            }

            return "internalsystem@razorpay.com";
        }

        return $user->getEmail();
    }
}
