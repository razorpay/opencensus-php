<?php

namespace RZP\Services;

use Request;
use Throwable;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use \WpOrg\Requests\Session as Requests_Session;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\lib\TemplateEngine;
use RZP\Http\Request\Hooks;
use RZP\Exception\TwirpException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestValidationFailureException;

class Stork
{
    // Request timeout in milliseconds for all HTTP requests to stork.
    const REQUEST_TIMEOUT = 2000;
    // Request connect timeout in milliseconds for all HTTP requests to stork.
    // Request timeout parameter applies after connection is established.
    const REQUEST_CONNECT_TIMEOUT = 2000;

    // path to send whatsapp message
    const WHATSAPP_SEND_MSG_PATH = '/twirp/rzp.stork.message.v1.MessageAPI/Create';

    // path to sendSms
    const SEND_SMS_PATH = '/twirp/rzp.stork.sms.v1.SMSAPI/Send';

    // path to setRateLimitThreshold
    const SET_RATE_LIMIT_PATH = '/twirp/rzp.stork.sms.v1.SMSAPI/SetRateLimit';

    // path to deleteRateLimitThreshold
    const DELETE_RATE_LIMIT_PATH = '/twirp/rzp.stork.sms.v1.SMSAPI/DeleteRateLimit';

    // path to removeSuppressionListEntry
    const REMOVE_FROM_SUPPRESSION_LIST_PATH = '/twirp/rzp.stork.suppression.v1.SuppressionAPI/RemoveFromSuppressionList';

    // mocked response params for the sendSms route.
    const MESSAGE_ID          = 'message_id';
    const TEST_MESSAGE_ID     = '10000000000msg';
    const SERVICE             = 'service';
    const TEST_SERVICE        = 'api-test';
    const OWNER_ID            = 'owner_id';
    const TEST_OWNER_ID       = '1000000000';
    const OWNER_TYPE          = 'owner_type';
    const TEST_OWNER_TYPE     = 'merchant';
    const TEMPLATE            = 'template';
    const TEST_TEMPLATE       = 'sms.test.template';
    const CONTEXT             = 'context';

    const THROW_SMS_EXCEPTION_IN_STORK  = 'THROW_SMS_EXCEPTION_IN_STORK';

    /**
     * Name of owning service for requests to stork.
     * @var string
     */
    public $service;

    /**
     * @var Requests_Session
     */
    public $request;

    /**
     * @var \RZP\Services\Aws\Sns
     */
    protected $sns;

    /**
     * @var \Razorpay\Trace\Logger
     */
    protected $trace;

    public function __construct()
    {
        $this->sns   = app('sns');
        $this->trace = app('trace');
    }

    /**
     * This method implements method for admin dashboard via stork service.
     * See \RZP\Constants\Entity's $externalServiceClass.
     *
     * @param  string $entity - Name of entity e.g. webhooks, messages.
     * @param  array  $input  - Request query/input.
     * @return array
     * @throws BadRequestValidationFailureException
     */
    public function fetchMultiple(string $entity, array $input): array
    {
        $this->init(app('rzp.mode'));

        switch ($entity)
        {
            case 'webhook':
                $storkInput = array_only($input, ['owner_id']);
                $storkInput['limit'] = $input['count'] ?? 10;
                $storkInput['offset'] = $input['skip'] ?? 0;

                $res = $this->requestAndGetParsedBody('/twirp/rzp.stork.webhook.v1.WebhookAPI/List', $storkInput);

                return $res['webhooks'] ?? [];

            default:
                throw new BadRequestValidationFailureException("Invalid entity name: {$entity}");
        }
    }

    /**
     * This method implements method for admin dashboard via stork service.
     * See \RZP\Constants\Entity's $externalServiceClass.
     *
     * @param  string $entity - Name of entity e.g. webhooks, messages.
     * @param  string $id     - Entity id.
     * @param  array  $input  - Request query/input.
     * @return array
     * @throws BadRequestValidationFailureException
     */
    public function fetch(string $entity, string $id, array $input): array
    {
        $this->init(app('rzp.mode'));

        switch ($entity)
        {
            case 'webhook':
                $storkInput = [];
                $storkInput['webhook_id'] = $id;

                $res = $this->requestAndGetParsedBody('/twirp/rzp.stork.webhook.v1.WebhookAPI/Get', $storkInput);

                return $res['webhook'] ?? [];

            default:
                throw new BadRequestValidationFailureException("Invalid entity name: {$entity}");
        }
    }

    public function init(string $mode, string $product = Product::PRIMARY)
    {
        $config = config('stork');

        // E.g. api-live, beta-api-live, rx-test, omega-rx-test etc.
        $this->service = $config['service_prefix'] . $config['auth'][$product][$mode]['user'];

        // For api i.e. pg and rx both, same user and password is used to authenticate request.
        $auth = [
            $config['auth'][Product::PRIMARY][$mode]['user'],
            $config['auth'][Product::PRIMARY][$mode]['pass']
        ];

        // Options and authentication for requests.
        $options = [
            'auth'  => $auth,
            'hooks' => new Requests_Hooks(),
        ];

        // This will add extra hook onto options[hooks] for dns resolution to
        // ipV4 only. Doing this for internal services only.
        $hooks = new Hooks($config['url']);
        $hooks->addCurlProperties($options);

        // Sets request timeout in milliseconds via curl options.
        $this->setRequestTimeoutOpts($options, self::REQUEST_TIMEOUT, self::REQUEST_CONNECT_TIMEOUT);

        // Instantiate a request instance.
        $this->request = new Requests_Session(
            $config['url'],
            // Common headers for requests.
            [
                'X-Request-ID' => Request::getTaskId(),
                'Content-Type' => 'application/json',
            ],
            [],
            $options
        );
    }

    /**
     * @param array $options
     * @param int   $timeoutMs
     * @param int   $connectTimeoutMs
     */
    public function setRequestTimeoutOpts(array &$options, int $timeoutMs, int $connectTimeoutMs)
    {
        $options += [
            'timeout'         => $timeoutMs,
            'connect_timeout' => $connectTimeoutMs,
        ];

        // Additionally sets request timeout in milliseconds via curl options.
        $options['hooks']->register(
            'curl.before_send',
            function ($curl) use ($timeoutMs, $connectTimeoutMs)
            {
                curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeoutMs);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeoutMs);
            });
    }

    /**
     * @param  string $path
     * @param  array  $payload
     * @return array
     * @throws ServerErrorException
     * @throws TwirpException
     */
    public function requestAndGetParsedBody(string $path, array $payload): array
    {
        $res = $this->request($path, $payload);

        // Returns parsed body..
        $parsedBody = json_decode($res->body, true);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $parsedBody;
        }

        // Else throws exception.
        throw new ServerErrorException(
            'Received invalid response body',
            ErrorCode::SERVER_ERROR_STORK_FAILURE,
            ['path' => $path, 'body' => $res->body]
        );
    }

    /**
     *
     * Makes call to stork service to send an SMS. By default if it's test mode
     * sendSms returns a mock success response. But conditionally callee can specify
     * if it should not mock test mode behavior.
     *
     * @param string       $mode -> live/test
     * @param array        $input
     * @param bool|boolean $mockInTestMode
     *
     * @throws Throwable
     * @return array
     */
    public function sendSms(string $mode, array $input, bool $mockInTestMode = true): array
    {
        $this->init($mode);

        if (($mode === Mode::TEST) and ($mockInTestMode === true))
        {
            return [
                self::MESSAGE_ID => self::TEST_MESSAGE_ID,
                self::SERVICE    => self::TEST_SERVICE,
                self::OWNER_ID   => self::TEST_OWNER_ID,
                self::OWNER_TYPE => self::TEST_OWNER_TYPE,
                self::CONTEXT    => [
                    self::TEMPLATE => self::TEST_TEMPLATE
                ],
            ];
        }
        else
        {
            try {
                $requestPayload = [
                    'service'                     => $this->service,
                    'owner_id'                    => $input['ownerId'],
                    'owner_type'                  => $input['ownerType'],
                    'org_id'                      => $input['orgId'],
                    'context'                     => $this->appendOrgIdInContext($input),
                    'sender'                      => $input['sender'],
                    'destination'                 => $input['destination'],
                    'template_name'               => $input['templateName'],
                    'template_namespace'          => $input['templateNamespace'],
                    'language'                    => $input['language'],
                    'content_params'              => $input['contentParams'] ?? json_decode('{}'),
                    'delivery_callback_requested' => $input['deliveryCallbackRequested'] ?? false,
                ];

                $this->traceSmsRequest($requestPayload);

                return $this->requestAndGetParsedBody(self::SEND_SMS_PATH, $requestPayload);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::STORK_SMS_REQUEST_FAILED
                );

                // Raven throws exception hence
                if (empty($input[self::THROW_SMS_EXCEPTION_IN_STORK]) === false and
                    $input[self::THROW_SMS_EXCEPTION_IN_STORK] === true)
                {
                    throw $e;
                }

                return [];
            }
        }
    }

    /**
     * If org id is not passed in stork context then copy from the orgId param.
     * @param array $input
     * @return mixed
     */
    private function appendOrgIdInContext(array $input)
    {
        if (empty($input['stork']['context']['org_id']) === true)
        {
            $input['stork']['context']['org_id'] = $input['orgId'];
        }

        return $input['stork']['context'];
    }

    /**
     * Identifies that the number has given consent
     * to Razorpay for communication via WhatsApp.
     *
     * @param string $mode
     * @param string $number
     * @param array $input
     * @return array
     * @throws ServerErrorException
     * @throws TwirpException
     */
    public function optInForWhatsapp(string $mode, string $number, array $input)
    {
        $this->init($mode);

        $storkInput = [
            'phone_number'         => $number,
            'source'               => $input['source'],
            'send_welcome_message' => $input['send_welcome_message'] ?? true,
        ];

        if(array_key_exists('business_account',$input))
        {
            $storkInput['business_account'] = $input['business_account'];
        }

        return $this->requestAndGetParsedBody('/twirp/rzp.stork.whatsapp.v1.WhatsappAPI/OptInUser', $storkInput);
    }

    public function optInStatusForWhatsapp(string $mode, string $number, string $source, string $businessAccount = '')
    {
        $this->init($mode);

        $storkInput = [
            'phone_number'      => $number,
            'source'            => $source,
        ];

        if(!empty($businessAccount))
        {
            $storkInput['business_account'] = $businessAccount;
        }

        return $this->requestAndGetParsedBody('/twirp/rzp.stork.whatsapp.v1.WhatsappAPI/GetUserConsent', $storkInput);
    }

    /**
     * Identifies that the number has revoked the consent
     * to Razorpay for communication via WhatsApp.
     *
     * @param string $mode
     * @param string $number
     * @param string $source source is the identifier which is making the
     *                       opt out request. For eg api.merchant.onboarding
     * @return array
     * @throws ServerErrorException
     * @throws TwirpException
     */
    public function optOutForWhatsapp(string $mode, string $number, string $source, string $businessAccount = '')
    {
        $this->init($mode);

        $storkInput = [
            'phone_number'      => $number,
            'source'            => $source,
        ];

        if(!empty($businessAccount))
        {
            $storkInput['business_account'] = $businessAccount;
        }

        return $this->requestAndGetParsedBody('/twirp/rzp.stork.whatsapp.v1.WhatsappAPI/OptOutUser', $storkInput);
    }

    /**
     * Makes call to stork service to send a message via Whatsapp
     * Please always add `template_name` to the $input.
     *
     * @param string $mode -> live/test
     * @param string $template
     * @param string|null $receiver
     * @param array $input
     * @return array
     */
    public function sendWhatsappMessage(string $mode, string $template, ?string $receiver, array $input)
    {
        if(empty($receiver))
        {
            return;
        }

        $this->init($mode);

        $requestPayload = [];

        try {
            $text = (new TemplateEngine)->render($template, $input['params']);

            $context = json_decode('{}');

            if (isset($input['template_name']) === true)
            {
                $context = json_decode(json_encode(['template' => $input['template_name']]));
            }

            if (isset($input['is_cta_template']) == true and isset($input['public_file_url']) == true)
            {
                $whatsappChannels = json_decode(json_encode([
                    'destination'=>$receiver,
                    'text'=>$text,
                    'is_cta_template'=>$input['is_cta_template'],
                    'button_url_param'=>$input['button_url_param'],
                    'attachment'=> [
                        'public_file_url'=>$input['public_file_url'],
                        'display_name'=>$input['display_name'],
                        'extension'=>$input['extension'],
                        'msg_type'=>$input['msg_type'],
                    ]
                ]));
            }

            else {
                if (isset($input['is_cta_template']) == true and isset($input['button_url_param']) == true) {
                    $whatsappChannels = json_decode(json_encode([
                        'destination' => $receiver,
                        'text' => $text,
                        'is_cta_template' => $input['is_cta_template'],
                        'button_url_param' => $input['button_url_param'],
                    ]));
                }
                else {
                    $whatsappChannels = json_decode(json_encode([
                        'destination' => $receiver,
                        'text' => $text,
                    ]));
                }
            }

            if ((isset($input['is_multimedia_template']) == true) and
                (isset($input['multimedia_payload']) === true) and
                ($input['is_multimedia_template'] == true))
            {

                $multmediaPayload               = $input["multimedia_payload"];
                $multmediaPayload["text"]       = $text;


                // Create payload as per: https://idocs.razorpay.com/platform/stork/integrate-stork/whatsapp/#send-a-multimedia-message
                $whatsappChannels = json_decode(json_encode($multmediaPayload));
            }

            $requestPayload = [
                'message' => [
                    'service'           => $this->service,
                    'owner_id'          => $input['ownerId'],
                    'owner_type'        => $input['ownerType'],
                    'context'           => $context,
                    'whatsapp_channels' => [
                        $whatsappChannels
                    ]
                ]
            ];

            $this->traceWhatsAppRequest($requestPayload);

            return $this->requestAndGetParsedBody(self::WHATSAPP_SEND_MSG_PATH, $requestPayload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::STORK_WHATSAPP_MESSAGE_FAILED
            );
        }
    }

    /**
     * @param  string $path
     * @param  array  $payload
     * @return \WpOrg\Requests\Response
     * @throws ServerErrorException
     * @throws TwirpException
     */
    public function request(string $path, array $payload, int $timeoutMs = null): \WpOrg\Requests\Response
    {
        $options = [];
        if ($timeoutMs !== null)
        {
            $options = ['hooks' => new Requests_Hooks()];
            $this->setRequestTimeoutOpts($options, $timeoutMs, $timeoutMs);
        }

        $res = null;
        $exception = null;
        $maxAttempts = 2;

        while ($maxAttempts--)
        {
            try
            {
                $res = $this->request->post($path, [], empty($payload) ? '{}' : json_encode($payload), $options);
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e);
                $exception = $e;
                continue;
            }

            // In case it succeeds in another attempt.
            $exception = null;
            break;
        }

        // An exception is thrown by lib in cases of network errors e.g. timeout etc.
        if ($exception !== null)
        {
            throw new ServerErrorException(
                "Failed to complete request",
                ErrorCode::SERVER_ERROR_STORK_FAILURE,
                ['path' => $path],
                $exception
            );
        }

        // If response was received but was not a success e.g. 4XX, 5XX, etc then
        // throws a wrapped exception so api renders it in response properly.
        if ($res->success === false)
        {
            throw new TwirpException(json_decode($res->body, true));
        }

        return $res;
    }

    /**
     * Publishes payload to a sns topic which stork has subscribed to. Note that
     * stork expects payload to be a cache invalidation request and nothing else.
     *
     * @param  array  $payload
     * @return void
     */
    public function publishOnSns(array $payload)
    {
        $this->sns->publish(json_encode($payload), 'stork');
    }

    protected function traceSmsRequest($request)
    {
        unset($request['content_params']);

        if (isset($request['destination']) === true)
        {
            $request['destination'] = mask_phone($request['destination']);
        }

        $this->trace->info(TraceCode::STORK_SMS_REQUEST, $request);
    }

    protected function traceWhatsAppRequest($request)
    {
        unset($request['message']['whatsapp_channels']['text']);

        if (isset($request['message']['whatsapp_channels']['destination']) === true)
        {
            $request['message']['whatsapp_channels']['destination'] = mask_phone($request['message']['whatsapp_channels']['destination']);
        }

        $this->trace->info(TraceCode::STORK_WHATSAPP_REQUEST, $request);
    }

    public function setMerchantTemplateRateLimitThreshold(array $payload)
    {
        $this->init(app('rzp.mode'));
        return $this->requestAndGetParsedBody(self::SET_RATE_LIMIT_PATH, $payload);
    }

    public function deleteMerchantTemplateRateLimitThreshold(array $payload)
    {
        $this->init(app('rzp.mode'));
        return $this->requestAndGetParsedBody(self::DELETE_RATE_LIMIT_PATH, $payload);
    }

    public function removeSuppressionListEntry(array $payload)
    {
        $this->init(app('rzp.mode'));
        return $this->requestAndGetParsedBody(self::REMOVE_FROM_SUPPRESSION_LIST_PATH, $payload);
    }
}
