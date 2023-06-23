<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use Redirect;
use ApiResponse;

use RZP\Exception;
use RZP\Models\Admin;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use Razorpay\IFSC\Bank;
use RZP\Services\NbPlus;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BharatQr;
use Razorpay\Trace\Logger;
use RZP\Http\RequestHeader;
use RZP\Gateway\Base\Action;
use RZP\Base\RuntimeManager;
use RZP\Models\Gateway\Rule;
use RZP\Models\Payment\Gateway;
use Exception as BaseException;
use RZP\Models\Gateway\Downtime;
use RZP\Gateway\Mozart as Mozart;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Upi\Base as BaseUpi;
use Illuminate\Http\RedirectResponse;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Jobs\DynamicNetBankingUrlUpdater;
use RZP\Models\Payment\Processor\UpiTrait;
use RZP\Gateway\Utility as GatewayUtility;
use RZP\Gateway\Netbanking\Base\Repository;
use RZP\Gateway\Enach\Npci\Netbanking as EnachNb;
use RZP\Models\Gateway\Priority as GatewayPriority;
use RZP\Services\UpiPayment\Service as UpiPaymentService;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields as AmazonResponse;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction as p2pUpiAxisActions;
use RZP\Models\Gateway\Downtime\Webhook\Constants\Vajra as VajraConstants;

class GatewayController extends Controller
{
    use UpiTrait;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

    }

    /**
     * This is a health Check API for third party url.
     * It basically hits external services (like payment gateway) through api.
     * This helps in tracking downtime of services which can be accessed only from inside
     * api ( like the ones which require VPN connectivity, whitelisted IPs, or custom
     * client certs etc)
     *
     * Returns the http status code it gets from the gateway as-it-is to the client.
     * In case of time out, it returns status code 504 with curl error message in
     * `error_message` field
     *
     * Request Params:
     * request params are same as the Requests lib(https://requests.ryanmccue.info/)'s params:
     * Except url, everything is optional
     *
     * url:
     * headers:                  (defaults to [])
     * content:                  (defaults to [])
     * method:                   (defaults to HEAD)
     * options:                  (defaults to 'timeout' => 60, 'verify' => false)
     */
    public function getExternalApiHealth(Downtime\Service $service)
    {
        $input = Request::all();

        $response = $service->getExternalApiHealth($input);

        return ApiResponse::json($response, $response['http_status']);
    }

    public function callbackUpiAirtel()
    {
        $this->callbackGateway('upi_airtel');
    }

    protected function processServerCallback($input, $gatewayDriver)
    {
        $startTime = microtime(true);

        $gateway = $this->app['gateway']->gateway($gatewayDriver);

        // Some gateways may need some pre-processing on the input
        // to be able to call the next few methods.
        //
        // Eg: gateway request needs to be decrypted, this shouldn't be direct method call
        // TODO: change this to utilize callGatewayFunction
        $input = $this->preProcessServerCallback($gateway, $input, $gatewayDriver);

        if (isset($input['upi_mandate']) === true and (isset($input['upi_mandate']['status']) === true))
        {
            return $this->processMandateServerCallback($input, $gatewayDriver);
        }

        if (Gateway::isUpiRecurringSupportedGateway($gatewayDriver) === true)
        {
            $redirect = $gateway->redirectCallbackIfRequired($input);

            if (empty($redirect) === false)
            {
                return $redirect;
            }
        }

        // TODO: this should also utilize callGatewayFunction, although we should have
        // used preProcessServerCallback itself to return it in some way
        $paymentId = $gateway->getPaymentIdFromServerCallback($input, $gatewayDriver);

        $paymentRepo = $this->app['repo']->payment;

        // This is hackish, we find mode based on searching in both DB's
        $mode = $paymentRepo->determineLiveOrTestModeForEntityWithGateway($paymentId, $gatewayDriver);

        if ($mode === null)
        {
            if ($this->shouldRoutePreProcessedCallbackThroughReArch($paymentRepo, $paymentId, $input) === true)
            {
                $data = $this->app['pg_router']->sendStaticCallbackRequestToPgRouter($paymentId, $input);

                $this->logCallbackResponseTime($startTime, $gatewayDriver, true);

                return $data;
            }

            $data =  $this->processNonExistingPaymentCallback($input, $paymentId, $gatewayDriver, true);

            $this->logCallbackResponseTime($startTime, $gatewayDriver, false, true);

            return $data;
        }
        else
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            $paymentId = Payment\Entity::getSignedId($paymentId);

            $payment = $this->repo->payment->findByPublicId($paymentId);

            if ($this->shouldSkipUpiICICICallback($payment, $mode, $input) === true)
            {

                $this->trace->info(TraceCode::SKIP_UPI_ICICI_CALLBACK_PROCESSING,
                    [
                        'payment_id' => $payment->getId(),
                        'merchant_id' => $payment->getMerchantId(),
                    ]);

                return [
                    'success' => true,
                ];

            }

            $data = (new Payment\Service)->s2sCallback($paymentId, $input);

            $this->logCallbackResponseTime($startTime, $gatewayDriver);

            return $data;
        }
    }

    /**
     * pushes the time taken for callback processing
     *
     * @param float $startTime
     * @param string $gateway
     * @return void
     */
    private function logCallbackResponseTime(float $startTime, string $gateway, bool $rearch = false, bool $unexpected = false)
    {
        try
        {
            $responseTime = get_diff_in_millisecond($startTime);

            $route  = $this->app['api.route']->getCurrentRouteName();

            $dimensions = [
                "payment_gateway"                       => $gateway,
                Payment\Metric::PAYMENT_REQUEST_ROUTE   => $route,
                "payment_rearch"                        => $rearch,
                "unexpected"                            => $unexpected,
            ];

            $this->trace->histogram(Payment\Metric::PAYMENT_UPI_CALLBACK_REQUEST_TIME, $responseTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_CALLBACK_ERROR_LOGGING_RESPONSE_TIME_METRIC
            );
        }
    }

    protected function preProcessServerCallback($gateway, $input, $gatewayDriver)
    {
        try
        {
            if ($this->shouldPreProcessThroughUpiPaymentService($gatewayDriver) === true)
            {
                try
                {
                    return $this->app['upi.payments']->preProcessServerCallback($input, $gatewayDriver);
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException(
                        $e,
                        Logger::WARNING,
                        TraceCode::UPI_PAYMENT_SERVICE_PRE_PROCESS_FAILURE);
                }
            }

            // pre-process callback if ups pre-processing fails
            return $gateway->preProcessServerCallback($input, $gatewayDriver);
        }
        catch (Exception\GatewayErrorException $exception)
        {
            $this->trace->traceException($exception, Logger::INFO, TraceCode::GATEWAY_DECRYPTION_FAILED);

            // As of now, we will consider two checks for gateway identification
            // and one check for method identification, this approach is only for UPI yet.
            // Note: Any other method would require to extend the function getTerminalDataFromCallback
            if(($exception->getCode() === ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED) and
               ($exception->getSafeRetry() === true) and
               ($gateway instanceof BaseUpi\Gateway))
            {
                $data = $gateway->getTerminalDetailsFromCallback($input);

                $terminal = $this->app['repo']->terminal->findByGatewayAndTerminalData($gatewayDriver, $data, false, Mode::LIVE);

                if (empty($terminal) === true)
                {
                    throw new Exception\RuntimeException(
                        'No terminal found',
                        [
                            'input'     => $input,
                            'data'      => $data,
                            'gateway'   => $gatewayDriver,
                        ],
                        $exception,
                        ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);
                }

                $terminal = $terminal->toArrayWithPassword();

                $gateway->setTerminal($terminal);

                return $gateway->preProcessServerCallback($input);
            }

            throw $exception;
        }
    }

    protected function processServerCallbackWithGatewayResponse($input, $gatewayDriver)
    {
        $startTime = microtime(true);

        $gateway = $this->app['gateway']->gateway($gatewayDriver);

        if ($this->shouldSkipUpiAirtelRefundCallback($gatewayDriver, $input) === true)
        {
            $this->trace->info(TraceCode::MISC_TRACE_CODE, [
                'gateway'   => $gatewayDriver,
                'message'   => 'Refund Callback',
            ]);

            return [
                'success' => true,
            ];
        }

        $input = $this->preProcessServerCallback($gateway, $input, $gatewayDriver);

        if ((isset($input['upi_mandate']) === true) and
            (isset($input['upi_mandate']['status']) === true))
        {
            return $this->processMandateServerCallback($input, $gatewayDriver);
        }

        $routeName = $this->app['api.route']->getCurrentRouteName();

        if ((Gateway::isUpiRecurringSupportedGateway($gatewayDriver) === true) and
            ($routeName === 'gateway_payment_callback_recurring'))
        {
            try
            {
                $headers = Request::header();
                $content = Request::getContent();

                $redirect = $gateway->redirectCallbackIfRequired($input, $content, $headers);
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException($exception, Logger::CRITICAL, TraceCode::PAYMENT_CALLBACK_FAILURE);

                return $gateway->postProcessServerCallback($input, $exception);
            }

            if (empty($redirect) === false)
            {
                return $redirect;
            }
        }

        $paymentId = $gateway->getPaymentIdFromServerCallback($input, $gatewayDriver);

        $paymentRepo = $this->app['repo']->payment;

        if ($gatewayDriver === Gateway::GOOGLE_PAY)
        {
            $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');
            if (is_null($mode) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                    null,
                    [
                        'method'      => 'card',
                        'application' => 'google_pay'
                    ]);
            }

            $this->app['basicauth']->setModeAndDbConnection($mode);

            $payment = $paymentRepo->find($paymentId);

            $gateway->validateCallbackRequest($input, $payment);
        }
        else
        {
            $mode = $paymentRepo->determineLiveOrTestModeForEntityWithGateway($paymentId, $gatewayDriver);
        }

        $postInput = [
            'gateway' => $input,
        ];

        try
        {
            if ($mode === null)
            {
                if ($this->shouldRoutePreProcessedCallbackThroughReArch($paymentRepo, $paymentId, $input) === true)
                {
                    $data = $this->app['pg_router']->sendStaticCallbackRequestToPgRouter($paymentId, $input);

                    $this->logCallbackResponseTime($startTime, $gatewayDriver, true);
                }
                else
                {
                    $data = $this->processNonExistingPaymentCallback($input, $paymentId, $gatewayDriver, true);

                    $this->logCallbackResponseTime($startTime, $gatewayDriver, false, true);
                }
            }
            else
            {
                $this->app['basicauth']->setModeAndDbConnection($mode);

                $paymentId = Payment\Entity::getSignedId($paymentId);

                if ((in_array($gatewayDriver, Payment\Gateway::$s2sMandateCallbackGateways, true) === true) and
                    ($gateway->isMandateUpdateCallback($input) === true))
                {
                    $data = (new Payment\Service)->mandateUpdateCallback($paymentId, $input);
                }
                else
                {
                    $payment = $this->repo->payment->findByPublicId($paymentId);

                    if ($this->shouldSkipUpiICICICallback($payment, $mode, $input) === true)
                    {

                        $this->trace->info(TraceCode::SKIP_UPI_ICICI_CALLBACK_PROCESSING,
                            [
                                'payment_id' => $payment->getId(),
                                'merchant_id' => $payment->getMerchantId(),
                            ]);

                        return [
                            'success' => true,
                        ];

                    }

                    $data = (new Payment\Service)->s2sCallback($paymentId, $input);

                    $this->logCallbackResponseTime($startTime, $gatewayDriver);
                }
            }

            $response = $gateway->postProcessServerCallback($postInput);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception, Logger::CRITICAL, TraceCode::PAYMENT_CALLBACK_FAILURE);

            $response = $gateway->postProcessServerCallback($postInput, $exception);
        }

        return $response;
    }

    /**
     * checks if pre processed callback can be processed through Re-Arch flow
     *
     * @param mixed $paymentRepo
     * @param mixed $paymentId
     * @return boolean
     */
    protected function shouldRoutePreProcessedCallbackThroughReArch($paymentRepo, $paymentId, $input)
    {
        try
        {
            $payment = $paymentRepo->findOrFail($paymentId);
        }
        catch (\Throwable $th)
        {
            $this->trace->info(TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
            [
                "message" => $th->getMessage(),
            ]);

            return false;
        }

        if (empty($payment) === true)
        {
            return false;
        }

        if ($payment->isExternal() === false)
        {
            return false;
        }

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_PAYMENTS_CALLBACK_DATA, [
            'payment_id'    => $payment->getId(),
            'merchant_id'   => $payment->getMerchantId(),
            'terminal_id'   => $payment->getTerminalId(),
            'callback_data' => $input,
        ]);

        return true;
    }

    /**
     * When callback payment id is not found in payment table, it could be
     * 1. Present in QrCode entity for VA payments
     * 2. Unexpected payments made directly to VPA
     *
     * @return array|bool
     */
    protected function processNonExistingPaymentCallback($input, $paymentId, $gatewayDriver, $isCallback = false)
    {
        // First if mode is not found from payment repo, we will check with QR repo
        $qrRepo = $this->app['repo']->qr_code;

        $suffixLength = strlen(QrCode\Constants::QR_CODE_V2_TR_SUFFIX);

        $isQrV2Payment = false;
        // this checks will only be applicable for static QR code. For dynamic QR code,
        // bank will send the ref id generated during QR creation
        if ((strlen($paymentId) >= ($suffixLength + QrCode\Entity::ID_LENGTH)) and
            (str_ends_with($paymentId, QrCode\Constants::QR_CODE_V2_TR_SUFFIX)))
        {
            $paymentId = substr($paymentId, 0, QrCode\Entity::ID_LENGTH);
            $isQrV2Payment = true;
        }

        $mode = $qrRepo->determineLiveOrTestModeByMerchantReference($paymentId);

        if ($mode !== null)
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            if ($isQrV2Payment === false)
            {
                $gatewayClass = $this->app['gateway']->gateway($gatewayDriver);
                $data         = $gatewayClass->getParsedDataFromUnexpectedCallback($input);

                $terminal = $this->app['repo']->terminal->findByGatewayAndTerminalData($gatewayDriver, $data['terminal']);

                if ($terminal === null)
                {
                    throw new Exception\LogicException('No terminal found for QR Code Payment', null, $data);
                }

                if ($terminal->isQrV2Terminal() === true)
                {
                    $isQrV2Payment = true;
                }
            }

            if ($isQrV2Payment === true)
            {
                $this->trace->info(TraceCode::QR_PAYMENT_GATEWAY_CALLBACK, $input);

                $data = (new BharatQr\Service)->processPayment($input, $gatewayDriver);
            }
            else
            {
                $data = (new QrCode\Upi\Service)->processPayment($input, $paymentId, $gatewayDriver);
            }
        }
        else
        {
            // We are disabling unexpected payments for some gateways,
            // this is either for new gateways or to delay refunds
            if (Gateway::isUnexpectedPaymentOnCallbackDisabled($gatewayDriver) === true)
            {
                unset($input['payment']['vpa']);

                $this->trace->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
                    'input'         => $input,
                    'gateway'       => $gatewayDriver,
                    'unexpected'    => 1,
                    'skipped'       => 1,
                ]);
                // Throw expection
                throw new Exception\RuntimeException('Unexpected payment on callback is not supported',[
                    'gateway' => $gatewayDriver,
                ]);
            }

            if (UniqueIdEntity::verifyUniqueId($paymentId, false) === true)
            {
                $this->trace->info(TraceCode::UPI_UNEXPECTED_PAYMENT_CREATION_SKIPPED, [
                    'payment_id'    => $paymentId,
                    'message'       => 'unexpected payment creation skipped due to length being 14'
                ]);

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_UNEXPECTED_PAYMENT_CREATION_SKIPPED);

                return [];
            }

            $data = (new Payment\Service)->unexpectedCallback($input, $paymentId, $gatewayDriver, $isCallback);
        }

        return $data;
    }

    protected function processMandateServerCallback($input, $gatewayDriver)
    {
        [$id, $mode] = $this->app['repo']->upi_mandate->determineIdAndLiveOrTestModeForEntityWithUMN($input['upi_mandate']['umn']);

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'UMN not found in either database',
                null,
                [
                    'gateway'    => $gatewayDriver,
                    'umn'        => $input['umn'],
                ]);
        }
        else
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            $id = Entity::getSignedId($id);

            switch($input['upi_mandate']['status'])
            {
                case 'pause':
                    return (new Payment\Service)->mandatePauseCallback($id, $input, $gatewayDriver);
                case 'resume':
                    return (new Payment\Service)->mandateResumeCallback($id, $input, $gatewayDriver);
                case 'revoke':
                    return (new Payment\Service)->mandateCancelCallback($id, $input, $gatewayDriver);
            }
        }
    }

    protected function callbackEbs($input)
    {
        $gateway = $this->app['gateway']->gateway('ebs');

        //TODO validate callback

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        \Database\DefaultConnection::set($mode);

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'Payment id not found in either database',
                null,
                [
                    'payment_id' => $paymentId
                ]);
        }

        $this->app['basicauth']->setMode($mode);

        $paymentId = Payment\Entity::getSignedId($paymentId);

        return $this->service('payment')->s2sCallback($paymentId, $input);
    }

    public function callbackGetsimpl()
    {
        $input = Request::all();
        $data = $this->processGetSimplCallback($input);
        return $data;
    }

    public function callbackGateway($gateway)
    {
        $input = Request::all();

        $data = [];

        $trace = $this->app['trace'];

        $traceInput = $input;

        $data = ( new GatewayUtility())->gatewayTrace($gateway, $traceInput);

        switch ($gateway)
        {
            // Standard Cases
            case Gateway::WALLET_FREECHARGE:
            case Gateway::BILLDESK:
            case Gateway::NETBANKING_AXIS:
            case Gateway::WALLET_PHONEPE:
            case Gateway::UPI_CITI:
            case Gateway::CRED:
            case 'axis_corporate':
                // TODO : Remove before prod merge. temporary hack for testing.
                if ($gateway === 'axis_corporate')
                {
                    $gateway = Gateway::NETBANKING_AXIS;
                }
                $data = $this->processServerCallback($input, $gateway);
                break;

            // Only logs the response
            case Gateway::WALLET_OLAMONEY:
                break;
            case Gateway::GETSIMPL:
                $data = $this->processGetSimplCallback($input);
                return $data;

            //Special case because gateway is upi_mindgate
            case 'upi_hdfc':
                $data = $this->processServerCallbackWithGatewayResponse($input, Payment\Gateway::UPI_MINDGATE);
                break;

            // Special case because we need the raw request body
            case Gateway::UPI_RBL:
            case Gateway::UPI_AIRTEL:
            case Gateway::UPI_AXISOLIVE:
            case Gateway::UPI_KOTAK:
            case Gateway::UPI_RZPRBL:
                $input = Request::getContent();
                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);
                break;

            case Gateway::UPI_ICICI:
                $input = Request::getContent();

                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);

                break;

            case Gateway::UPI_JUSPAY:

                if ($this->shouldProcessThroughPspxService($input) === true)
                {
                    Request::getFacadeRoot()->route()->setParameter('gateway', 'p2p_upi_axis');

                    return (new P2p\UpiController)->gatewayCallback();
                }

                $input = [
                    'headers' => [
                        'x-merchant-payload-signature' => Request::header('X-Merchant-Payload-Signature')
                    ],
                    'raw'     => Request::getContent(),
                    'body'    => $input,
                ];

                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);

                break;

            case Gateway::UPI_HULK:
                $input['headers'] = Request::header();
                $input['raw'] = Request::getContent();

                $data = $this->processServerCallback($input, Gateway::UPI_HULK);

                break;

            case Gateway::UPI_MINDGATE:
            case Gateway::UPI_SBI:
            case Gateway::UPI_AXIS:
            case Gateway::CASHFREE:
            case Gateway::PAYTM:
            case Gateway::OPTIMIZER_RAZORPAY:
            case Gateway::PAYU:
            case Gateway::CCAVENUE:
                // Used for PayU emandate as well, since gateway does not allow setting
                // separate URL for diff methods at their end.
                // Pls make sure changes in this flow, do not break for emandate.
                // In future, move UPI callback to staticS2SCallbackGatewayWithModeAndMethod
                // For emandate, already handled there.
                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);
                break;

            case Gateway::BILLDESK_OPTIMIZER:
                $input = [
                    'payment' => [
                        'gateway' => $gateway,
                    ],
                    'body'    => Request::getContent()
                ];
                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);
                break;
            // Need to whitelist upi_yesbank at bank end
            case Gateway::UPI_YESBANK:
            case 'upi_yesb':
                $input = Request::getContent();

                $data = $this->processServerCallbackWithGatewayResponse($input, Gateway::UPI_YESBANK);

                break;
        }

        // $input['gateway'] = $gateway;

        return ApiResponse::json($data);
    }

    public function staticCallbackGateway($method, $gateway, $mode, $gatewayInput = [])
    {
        $input = Request::all();

        $input = array_merge($input, $gatewayInput);

        $this->app['trace']->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'method'           => $method,
                'gateway'          => $gateway,
                'callback_data'    => $input,
                'mode'             => $mode,
            ]
        );

        $paymentId = $this->preProcessStaticCallback($method, $gateway, $input, $mode);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $mode = $this->app['rzp.mode'];

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        $publicPaymentId = $payment->getPublicId();

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $url = $url . '?' . http_build_query($input);

        return Redirect::to($url);
    }

    /**
     * This is a new route created to implement static S2S callbacks
     * This route returns a json response and takes method and mode as input as this is required to redirect the request to CPS
     * Internally this route executes the same s2sCallback() from the route - gateway_payment_static_callback_post/gateway_payment_static_callback_get
     */
    public function staticS2SCallbackGatewayWithModeAndMethod($method, $gateway, $mode)
    {
        $input = Request::all();

        $this->app['trace']->info(
            TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK,
            [
                'method'             => $method,
                'gateway'            => $gateway,
                'mode'               => $mode,
                'callback_data'      => $input,
            ]
        );

        $paymentId = $this->preProcessStaticCallback($method, $gateway, $input, $mode);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $publicPaymentId = $payment->getPublicId();

        $data = (new Payment\Service)->s2sCallback($publicPaymentId, $input);

        return ApiResponse::json($data);

    }

    public function callbackKotakCancel()
    {
        return $this->callbackKotak();
    }

    public function callbackKotakCorp()
    {
        $method = Payment\Method::NETBANKING;

        $gateway = Payment\Gateway::NETBANKING_KOTAK;

        $mode = ($this->app->isProduction()) ? Mode::LIVE: Mode::TEST;

        $input['method_type'] = 'corporate';

        $input['bank'] = Payment\Processor\Netbanking::KKBK_C;

        return $this->staticCallbackGateway($method, $gateway, $mode, $input);
    }

    public function callbackKotak()
    {
        $method = Payment\Method::NETBANKING;

        $gateway = Payment\Gateway::NETBANKING_KOTAK;

        $mode = ($this->app->isProduction()) ? Mode::LIVE: Mode::TEST;

        $input['method_type'] = 'retail';

        $input['bank'] = Bank::KKBK;

        return $this->staticCallbackGateway($method, $gateway, $mode, $input);
    }

    public function callbackCorporation()
    {
        $input = Request::keys();

        /**
         * They send the data in the below format:
         * https://api.razorpay.com/v1/gateway/netbanking_corporation/callback?6T9sZxc9z5XCQKsT3\
         * HdxWY+pj6wAIUp3tsrgEBjH5SM39o5QI3S9mTygY/ABkXtBtOdBsuImxJB91xz8K/bDxT9CcsOpjvT69XkK/uO\
         * xud6mk9KllE4ryN0v/DcO5xn/
         *
         * Since there is no value and just a key, we have to get the first key and use it as
         * the input to gateway
         */
        $input = $input[0];

        /**
         * For the input "xWY+pj6w" (say), the $input value would be "xWY_pj6w".
         * This is done internally by PHP. Refer: http://ca.php.net/variables.external
         *
         * > Dots and spaces in variable names are converted to underscores.
         * > For example <input name="a.b" /> becomes $_REQUEST["a_b"].
         *
         * The above applies for '+' as well. So, we convert this manually back to
         * the correct input which was received in the URL.
         */
        $input = str_replace('_', '+', $input);

        $gateway = $this->app['gateway']->gateway('netbanking_corporation');

        $input = $gateway->preProcessServerCallback($input);

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        $this->app['config']->set('database.default', $mode);

        $netbanking = $this->app['repo']->netbanking->findByPaymentIdAndAction(
            $paymentId,
            Action::AUTHORIZE
        );

        if ($netbanking === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite payment id: ' . $paymentId);
        }

        $publicPaymentId = $netbanking->getPublicPaymentId();

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $inputMsg = http_build_query($input);

        $url = $url . '?' . $inputMsg;

        return Redirect::to($url);
    }

    public function callbackCanara()
    {
        return $this->staticCallbackGateway('netbanking', 'netbanking_canara', 'live');
    }

    protected function checkMultipleCallback($payment)
    {
        $this->trace->info(TraceCode::PAYMENT_CALLBACK_RETRY);

        if ($payment->hasBeenAuthorized() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED, null, ['method' => $payment->getMethod()]);
        }

    }

    public function getCallbackMutexResource(Payment\Entity $payment): string
    {
        return 'callback_' . $payment->getId();
    }

    public function processGetSimplCallback($input)
    {
        $paymentId = $input['merchant_payload'];

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        $this->app['config']->set('database.default', $mode);

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $payment = $this->app['repo']->payment->findOrFail($paymentId);

        $merchant = $payment->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        $this->app['basicauth']->setAuthDetailsUsingPublicKey($publicKey);

        if ($payment->getCallbackUrl() !== null)
        {
            $this->app['rzp.merchant_callback_url'] = $payment->getCallbackUrl();
        }

        return $this->mutex->acquireAndRelease(
            $this->getCallbackMutexResource($payment),
            function() use ($input ,$merchant, $paymentId)
            {

                $payment = $this->app['repo']->payment->findOrFail($paymentId);

                $this->checkMultipleCallback($payment);

                if((isset($input['token']) === false) or ($input['token'] === "null"))
                {
                    $e = new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                        null,
                        [
                            'provider'   => Gateway::GETSIMPL,
                            'input'      => $input,
                            'payment_id' => $payment->getPublicId(),
                            'order_id'   => $payment->getOrderId(),
                        ]);

                    $processor = new Payment\Processor\Processor($merchant);

                    $processor->setPayment($payment);

                    $processor->updatePaymentAuthFailed($e);

                    throw $e;
                }

                $input = Mozart\GetSimpl\Helper::getPaymentInputParameters($input, $payment);

                $input['simpltoken'] = $input['token'];

                $gatewayinput = $input;

                $input['payment'] = $payment;

                //we are sending shopify and shopify-payment-app in metadata in the request but in callback flow these two fields are not coming.
                //Due to which some risks checks are failing. Therefore, adding all the fields in metadata before callback.

                $pa = $this->repo->payment_analytics->findLatestByPayment($payment->getId());

                $input['_'] = $pa ? $pa->toArray() : null;

                $payment->setMetadata($input);

                $data = (new Payment\Processor\Processor($merchant))->process($input, $gatewayinput);

                if ($this->app['rzp.mode'] === 'test')
                {
                    return $data;
                }

                assertTrue ($data !== null);

                if ((isset($data['request'])) and ($data['type'] === 'return'))
                {
                    return View::make('gateway.callbackReturnUrl')->with('data', $data);
                }

                return View::make('gateway.callback')->with('data', $data);

            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);
    }

    public function callbackYesbank()
    {
        $input = Request::all();

        /*
           this is required as the gateway sends raw encrypted string without urlencoding the same. So symbols such as
           + etc is interpreted by php as url encoded and the string obtained here will be different than what yes bank
           had sent and hence decryption would fail.
        */
        $originalEncryptedData = str_replace(
                                    ' ',
                                    '+',
                                     $input[Mozart\NetbankingYesb\ResponseFields::ENCRYPTED_RESPONSE]);

        $input[Mozart\NetbankingYesb\ResponseFields::ENCRYPTED_RESPONSE] = $originalEncryptedData;

        $this->app['trace']->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'gateway'          => 'netbanking_yesb',
                'encrypted_string' => $input
            ]
        );

        $gateway = $this->app['gateway']->gateway(Gateway::NETBANKING_YESB);

        $response = $gateway->preProcessServerCallback($input, Gateway::NETBANKING_YESB);

        $paymentId = $gateway->getPaymentIdFromServerCallback($response, Gateway::NETBANKING_YESB);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        $this->app['config']->set('database.default', $mode);

        $payment = $this->app['repo']->payment->findOrFail($paymentId);

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        $publicPaymentId = $payment->getPublicId();

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $url = $url . '?' . http_build_query(['preProcessServerCallbackResponse' => json_encode($response)]);

        return Redirect::to($url);
    }

    public function callbackEmandateNpciNb()
    {
        $input = Request::all();

        $this->app['trace']->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input'   => $input ,
                'gateway' => Gateway::ENACH_NPCI_NETBANKING,
            ]
        );

        $responseXml = (array) simplexml_load_string(trim($input[EnachNb\ResponseFields::RESPONSE_XML]));

        $json = json_encode($responseXml);

        $responseArray = json_decode($json,true);

        if($input[EnachNb\ResponseFields::RESPONSE_TYPE] === EnachNb\ResponseType::SUCCESS)
        {
            $paymentId = $responseArray[EnachNb\ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                       [EnachNb\ResponseXmlTags::ACCEPT_DETAILS]
                                       [EnachNb\ResponseXmlTags::ORIGINAL_MSG_INFO]
                                       [EnachNb\ResponseXmlTags::MANDATE_REQUEST_ID];
        }
        else
        {
            //TODO : what if payment id is not present : possible
            $paymentId = $responseArray[EnachNb\ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                       [EnachNb\ResponseXmlTags::ORIGINIAL_REQUEST_INFO]
                                       [EnachNb\ResponseXmlTags::MANDATE_REQUEST_ID];
        }

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        $this->app['config']->set('database.default', $mode);

        $this->app['basicauth']->setMode($mode);

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $publicPaymentId = $payment->getPublicId();

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $inputMsg = http_build_query($input);

        $url = $url . '?' . $inputMsg;

        return Redirect::to($url);
    }

    protected function preProcessStaticCallback($method, $gateway, $input, $mode)
    {
        $currentRoute = $this->route->getCurrentRouteName();

        Payment\Method::validateMethod($method);

        if (($gateway === null) or
            (Gateway::isStaticCallbackGateway($gateway) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                null,
                [
                    'gateway' => $gateway
                ]
            );
        }

        if (empty($input) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_RESPONSE_BODY,
                null,
                [
                    'input' => $input
                ]
            );
        }

        if ((($currentRoute === 'gateway_payment_static_callback_get') or
            ($currentRoute === 'gateway_payment_static_callback_post') or
            ($currentRoute === 'gateway_payment_static_s2scallback_get') or
            ($currentRoute === 'gateway_payment_static_s2scallback_post')) and
            (isset($mode) === false) or
            (Mode::exists($mode)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_INVALID_MODE
            );
        }

        $paymentId = $this->callGatewayPreprocessCallback($method, $gateway, $input, $mode);

        $paymentMode = $this->repo->determineLiveOrTestModeForEntity($paymentId, 'payment');

        if ($paymentMode !== $mode)
        {
            $this->app['rzp.mode'] = $paymentMode;

            $this->app['trace']->info(
                TraceCode::GATEWAY_PAYMENT_MODE_MISMATCH,
                [
                    'bank_mode'    => $mode,
                    'payment_mode' => $paymentMode,
                ]
            );
        }

        $this->config->set('database.default', $paymentMode);

        return $paymentId;
    }

    public function callbackAmazonpay($responseFormat = 'html')
    {
        $input = Request::all();

        if (isset($input[AmazonResponse::SELLER_ORDER_ID]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                [
                    'gateway' => Gateway::WALLET_AMAZONPAY,
                    'input'   => $input,
                ]);
        }

        $gateway = $this->app['gateway']->gateway(Gateway::WALLET_AMAZONPAY);

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $this->app['trace']->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway' => Gateway::WALLET_AMAZONPAY,
                'input'   => $input,
                'match'   => ($paymentId === $input[AmazonResponse::SELLER_ORDER_ID])
            ]);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        if (empty($mode) === true)
        {
            throw new Exception\LogicException(
                'Payment id not found in either database',
                null,
                [
                    'gateway'    => Gateway::WALLET_AMAZONPAY,
                    'payment_id' => $paymentId,
                ]);
        }

        \Database\DefaultConnection::set($mode);

        $this->app['basicauth']->setMode($mode);

        $payment = $this->repo->payment->findOrFailPublic($paymentId);
        $publicPaymentId = $payment->getPublicId();

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        switch ($responseFormat)
        {
            case 'ajax':
                $route = 'payment_callback_ajax_with_key_get';
                break;

            default:
                $route = 'payment_callback_with_key_get';
                break;
        }

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey, $route);

        $query = http_build_query($input);

        return Redirect::to($url . '?'. $query);
    }

    protected function getNetbankingEntityAndModeByTraceId($traceId)
    {
        $app = $this->app;

        /** @var Repository $repo */
        $repo = $app['repo']->netbanking;

        $mode = 'live';

        $app['config']->set('database.default', $mode);

        $nb = $repo->findByVerificationIdAndAction($traceId, Action::AUTHORIZE);

        if ($nb === null)
        {
            $mode = 'test';

            $app['config']->set('database.default', $mode);

            $nb = $repo->findByVerificationIdAndAction($traceId, Action::AUTHORIZE);
        }

        return ['nb' => $nb, 'mode' => $mode];
    }

    /**
     * Fetches list of all active downtimes as of now
     */
    public function getGatewayDowntimes(Downtime\Service $service)
    {
        $data = $service->getGatewayDowntimeDataForDashboard();

        return ApiResponse::json($data);
    }

    public function archiveGatewayDowntimes(Downtime\Service $service)
    {
        $data = $service->archiveGatewayDowntimes();

        return ApiResponse::json($data);
    }

    /**
     * Method to create a gateway downtime entity
     *
     * @param Downtime\Service $service
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGatewayDowntime(Downtime\Service $service)
    {
        $input = Request::all();

        $data = $service->create($input);

        return ApiResponse::json($data);
    }

    /**
     * Method to update gateway downtime entity
     *
     * @param Downtime\Service $service
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putGatewayDowntime(Downtime\Service $service, string $id)
    {
        $input = Request::all();

        $data = $service->edit($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * Method to delete gateway downtime entity
     *
     * @param Downtime\Service $service
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteGatewayDowntime(Downtime\Service $service, string $id)
    {
        $data = $service->delete($id);

        return ApiResponse::json($data);
    }

    /**
     * Method to handle webhook from vajra
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGatewayDowntimeVajraWebhook(Downtime\Service $service)
    {
        $input = Request::all();

        $data = $service->processGatewayDowntimeVajraWebhook($input);

        return ApiResponse::json($data);
    }

    /**
     * Method to handle cps downtime from vajra
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postCpsDowntimeVajraWebhook(Admin\Service $service)
    {
        $input = Request::all();

        $result = $this->setCpsRoutingFlag($service, $input);

        return ApiResponse::json($result);
    }

    public function postGatewayDowntimeServiceWebhook(Downtime\Service $service)
    {
        $input = Request::all();

        $data = $service->processDowntimeServiceWebhook($input);

        return ApiResponse::json($data);
    }

    public function postDowntimeSlackNotificationMerchants(Downtime\Service $service)
    {
        $input = Request::all();

        $service->updateDowntimeSlackNotifationMerchantNames($input);

        return ApiResponse::json(["success" => true]);
    }

    protected function setCpsRoutingFlag(Admin\Service $service, array $input)
    {
        $alertStatus = $input[VajraConstants::STATUS_KEY];

        $cpsRoutingStatus = ((bool) Admin\ConfigKey::get(Admin\ConfigKey::CPS_SERVICE_ENABLED, false));

        $trace = $this->app['trace'];

        $trace->info(
            TraceCode::VAJRA_CPS_ROUTING_REQUEST,
            ['data' => ['cpsRoutingStatus' => $cpsRoutingStatus, 'alertStatus' => $alertStatus]]
        );

        $result = [];

        switch ($alertStatus)
        {
            case VajraConstants::STATUS_ALERTING:
                $trace->info(
                    TraceCode::VAJRA_CPS_START_DISABLE_ROUTING,
                    []
                );

                if ($cpsRoutingStatus === true)
                {
                    $result = $service->setConfigKeys(
                        [Admin\ConfigKey::CPS_SERVICE_ENABLED => '0']
                    );

                    $trace->info(
                        TraceCode::VAJRA_CPS_DISABLE_ROUTING_SUCCESS,
                        ['data' => 'Successfully stopped traffic to CPS']
                    );

                }
                else
                {
                    $trace->info(
                        TraceCode::VAJRA_CPS_DISABLE_ROUTING_FAILURE,
                        ['data' => 'Couldnt stop traffic to CPS due to internal status mismatch']
                    );
                }

                break;

            case VajraConstants::STATUS_OK:
                $trace->info(
                    TraceCode::VAJRA_CPS_START_ENABLE_ROUTING,
                    []
                );

                if ($cpsRoutingStatus !== true)
                {
                    $result = $service->setConfigKeys(
                        [Admin\ConfigKey::CPS_SERVICE_ENABLED => '1']
                    );

                    $trace->info(
                        TraceCode::VAJRA_CPS_ENABLE_ROUTING_SUCCESS,
                        ['data' => 'Successfully enabled traffic to CPS']
                    );
                }
                else
                {
                    $trace->info(
                        TraceCode::VAJRA_CPS_ENABLE_ROUTING_FAILURE,
                        ['data' => 'Couldnt enable traffic to CPS due to internal status mismatch']
                    );
                }

                break;

            default:
                    $trace->info(
                        TraceCode::VAJRA_INVALID_ALERT_STATUS,
                        ['data' => 'Vajra alert status should be one of (alerting or ok)']
                    );

                break;
        }

        return $result;
    }

    /**
     * Method to handle webhook from statuscake
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGatewayDowntimeWebhook(Downtime\Service $service, $source)
    {
        $input = Request::all();

        $data = $service->processGatewayDowntimeWebhook($source, $input);

        return ApiResponse::json($data);
    }

    public function authorizePayment()
    {
        $gatewayInput = Request::all();

        $gateway = Gateway::GOOGLE_PAY;

        $data = $this->processServerCallbackWithGatewayResponse($gatewayInput, $gateway);

        return ApiResponse::json($data);
    }

    public function verifyPaymentStatus()
    {
        $gatewayInput = Request::all();

        $gateway = Gateway::GOOGLE_PAY;

        $data = $this->app['gateway']->call($gateway, Action::VERIFY, $gatewayInput, null, null);

        return ApiResponse::json($data);
    }

    /**
     * Single use function - Fills provider field in the UPI table with bank code
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fillUpiBank()
    {
        RuntimeManager::setMaxExecTime(1800);

        RuntimeManager::setMemoryLimit('1024M');

        $batchSize = 500;
        $lastId = 0;

        $totalRecords = $failedCount = $successCount = 0;
        $failedIds = [];

        while (true)
        {
            $recordsToUpdate = $this->repo->upi->fetchAllForBankUpdate($batchSize, $lastId);

            $currentBatchCount = count($recordsToUpdate);

            $totalRecords += $currentBatchCount;

            if ($currentBatchCount === 0)
            {
                break;
            }

            foreach ($recordsToUpdate as $upiRecord)
            {
                $provider = $upiRecord->extractProviderFromVpa();

                $bankCode = ProviderCode::getBankCode($provider);

                if ($bankCode === null)
                {
                    $failedCount++;

                    $failedIds[] = $upiRecord->getId();

                    $lastId = $upiRecord->getId();

                    continue;
                }

                $upiRecord->setBank($bankCode);

                try
                {
                    $this->repo->saveOrFail($upiRecord);

                    $successCount++;
                }
                catch (\Exception $ex)
                {
                    $failedCount++;

                    $failedIds[] = $upiRecord->getId();
                }

                $lastId = $upiRecord->getId();
            }

            if ($currentBatchCount < $batchSize)
            {
                break;
            }
        }

        return ApiResponse::json([
            'total_processed'    => $totalRecords,
            'total_success'      => $successCount,
            'total_fail'         => $failedCount,
            'failed_ids'         => implode(', ', $failedIds)
        ]);
    }

    public function createGatewayRule(Rule\Service $service)
    {
        $input = Request::all();

        $data = $service->create($input);

        return ApiResponse::json($data);
    }

    public function deleteGatewayRule(Rule\Service $service, string $id)
    {
        $data = $service->delete($id);

        return ApiResponse::json($data);
    }

    public function updateGatewayRule(Rule\Service $service, string $id)
    {
        $input = Request::all();

        $data = $service->update($id, $input);

        return ApiResponse::json($data);
    }

    public function updateNetbankingUrlInStatusCake()
    {
        $input = Request::all();

        $driver = null;

        switch($input['driver'])
        {
            case 'statuscake':
                $driver = DynamicNetBankingUrlUpdater::class;

            default:
                throw new BaseException('Invalid driver passed');
        }

        try
        {
            $driver::dispatch();
        }
        catch (\Throwable $exc)
        {
            $this->trace->error(TraceCode::STATUSCAKE_CRON_FAILED, [
                'driver'         => $driver,
                'exception' => $exc->getMessage(),
            ]);
        }
    }

    public function upiDataCorrection(BaseUpi\Service $service, string $action)
    {
        return $service->dataCorrection($action, Request::all());
    }

    protected function getMerchantKeyForPayment(Payment\Entity $payment, string $mode)
    {
        $publicKey = $payment->getPublicKey();

        if (empty($publicKey) === false)
        {
            return $publicKey;
        }

        $paymentOrderID = $payment->getApiOrderId();

        if ($paymentOrderID === null)
        {
            $paymentOrderID = $payment->getOrderId();
        }

        if (empty($paymentOrderID) === false)
        {
            $order = $this->repo->order->findOrFailPublic($paymentOrderID);

            $publicKey = $order->getPublicKey();

            if (empty($publicKey) === false)
            {
                return $publicKey;
            }
        }

        $key = $this->repo->key->getFirstActiveKeyForMerchant($payment->getMerchantId());

        if (empty($key) === false)
        {
            return $key->getPublicKey($mode);
        }

        // TODO: We can remove the log after successful validation
        $this->app['trace']->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, [
            'message'       => 'Key not found',
            'merchant_id'   => $payment->getMerchantId(),
            'gateway'       => $payment->getGateway(),
            'payment_id'    => $payment->getId(),
        ]);

        // Route class check on empty string
        return '';
    }

    protected function purgeGatewayDowntimeDetectionKeys(Downtime\Service $service)
    {
        $service->purgeKeys();

        return ApiResponse::json([
            'success' => true
        ]);
    }

    protected function gatewayDowntimeCron(Downtime\Service $service)
    {
        $input = Request::all();

        $service->createDowntimeIfNecessary($input);

        return ApiResponse::json([
            'success' => true
        ]);
    }

    protected function phonepeDowntimeCron(Downtime\Service $service)
    {
        $input = Request::all();

        $service->phonePeDowntime($input);

        return ApiResponse::json([
            'success' => true
        ]);
    }

    protected function callGatewayPreprocessCallback($method, $gatewayName, $input, $mode)
    {
        $variant = null;

        $mode = ($mode === null) ? Mode::LIVE : $mode;

        $this->app['rzp.mode'] = $mode;

        $bank = $input['bank'] ?? null;

        if (Gateway::gatewaysAlwaysRoutedThroughNbplusService($gatewayName, $bank, $method) === true)
        {
            $variant = 'nbplusps';
        }
        else if (Gateway::isNbPlusServiceGateway($gatewayName) === true)
        {
            // method is added as a part of feature flag because emandate and netbanking have same gateways.
            $featureFlag = $method . '_' . Payment\Processor\Processor::NB_PLUS_PAYMENTS_PREFIX;

            if (Payment\Gateway::gatewaysPartiallyMigratedToNbPlusWithBankCode($gatewayName))
            {
                // temporary code.. remove once hdfc completely migrated to nbplus
                // retail HDFC Nb have dynamic callback while corp has static callback route
                if($gatewayName === Gateway::NETBANKING_HDFC)
                {
                    $input['method_type'] = 'corporate';

                    $input['bank'] = Payment\Processor\Netbanking::HDFC_C;
                }

                $featureFlag .= '_' . strtolower($input['bank']);
            }

            $featureFlag .= '_' . $gatewayName;

            $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(), $featureFlag, $mode);
        }

        if (strtolower($variant) === 'nbplusps')
        {
            $inputData['gateway_data'] = $input;

            try
            {
                return $this->app['nbplus.payments']->action($method, $gatewayName, Nbplus\Action::PREPROCESS_CALLBACK, $inputData);
            }
            catch (\Exception $e)
            {
                $this->trace->info(TraceCode::NBPLUS_PAYMENT_SERVICE_ERROR,
                    [
                        'message'   => 'Unable to make request to nbplus service',
                        'gateway'   => $gatewayName,
                        'exception' => $e->getMessage(),
                        'action'    => NbPlus\Action::PREPROCESS_CALLBACK,
                    ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    null,
                    [
                        'input' => $input
                    ]);
            }
        }

        $gateway = $this->app['gateway']->gateway($gatewayName);

        $response = $gateway->preProcessServerCallback($input, $gatewayName, $mode);

        return $gateway->getPaymentIdFromServerCallback($response, $gatewayName);
    }

    protected function storeFirstDataPares()
    {
        $input = Request::all();

        if ((isset($input['gateway_merchant_id']) === false) or
            (isset($input['payment_id']) === false) or
            (isset($input['paRes']) === false))
        {
            $this->trace->error(TraceCode::GATEWAY_RAW_PARES_RESPONSE_REDIS_FAILURE, [
                'message' => 'validation failed for mandatory fields'
            ]);

            return ApiResponse::json([
                'status' => false,
                'message' => 'validation failed for mandatory fields',
            ]);
        }

        $str = $input['gateway_merchant_id'] . '|' . $input['payment_id'] . '|' .
            $input['paRes'];

        $key = \RZP\Gateway\FirstData\Gateway::PARES_DATA_CACHE_KEY . $input['payment_id'];

        $success = true;

        try
        {
            $this->app['cache']->put($key, $str, 60 * 60 * 25); // 1 day 1 hour
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::GATEWAY_RAW_PARES_RESPONSE_REDIS_FAILURE,
                ['key' => $key]);

            $success = false;
        }

        $this->trace->info(TraceCode::GATEWAY_RAW_PARES_RESPONSE, [
            'payment_id' => $input['payment_id'],
            'pares' => $input['paRes'],
            'store_id' => $input['gateway_merchant_id'],
        ]);

        return ApiResponse::json([
            'status' => $success,
            'payment_id' => $input['payment_id'],
        ]);
    }

    protected function getGatewayDowntimeForRouter(Downtime\Service $service)
    {
        $input = Request::all();

        try
        {
            $response = $service->getGatewayDowntimeDataForPayment($input);

            return ApiResponse::json($response);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FETCH_GATEWAY_DOWNTIME_ERROR);

            return ApiResponse::json([
                'status' => false,
                'message' => 'failed to fetch downtimes',
            ]);
        }
    }

    // Determines if callback should be preprocessed by UPS
    protected function shouldPreProcessThroughUpiPaymentService($gateway, $mode = null)
    {
        if (Payment\Gateway::isUpiPaymentServicePreProcessGateway($gateway) === false)
        {
            return false;
        }

        if ($this->isRearchBVTRequestForUPI(Request::header(RequestHeader::X_RZP_TESTCASE_ID)) === true)
        {
            return true;
        }

        $feature = 'ups'. '_' . $gateway . '_' . UpiPaymentService::PRE_PROCESS . '_' . 'v1';

        $mode = ($mode === null) ? Mode::LIVE : $mode;

        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
            $feature, $mode, 3);

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_PRE_PROCESS_RAZORX_VARIANT, [
            'gateway' => $gateway,
            'variant' => $variant,
            'mode'    => $mode,
            'feature' => $feature,
        ]);

        if ($variant === $gateway)
        {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the callback is a UPI Airtel Refund Callback
     * i.e. payer VPA handle is a merchant's VPA
     * and payee VPA handle is not a merchant's VPA
     * and the transaction ID is of length 14 (refund ID)
     *
     * @param $gatewayDriver
     * @param $input
     *
     * @return bool
     */
    protected function shouldSkipUpiAirtelRefundCallback($gatewayDriver, $input): bool
    {
        if ($gatewayDriver !== Gateway::UPI_AIRTEL)
        {
            return false;
        }

        $inputArray = json_decode($input, true);

        $payerVpa = $inputArray['payerVPA'] ?? '';
        $payeeVpa = $inputArray['payeeVPA'] ?? '';

        return ((str_ends_with($payerVpa, '@' . ProviderCode::MAIRTEL) === true) and
            (str_ends_with($payeeVpa, '@' . ProviderCode::MAIRTEL) === false) and
            (strlen($inputArray['hdnOrderID'] ?? '') === 14));
    }

    protected function shouldProcessThroughPspxService($input): bool
    {
        if (isset($input['type']) === false)
        {
            return false;
        }

        $p2pAxisActions = new \ReflectionClass(p2pUpiAxisActions::class);

        $callbackTypes = array_values($p2pAxisActions->getConstants());

        if (in_array($input['type'], $callbackTypes))
        {
            return true;
        }

        return false;
    }

    // Using this Controller, we will be hitting an external api
    // to get the downtime data for the fpx banks and using that data
    // downtime will be created and resolved
    protected function createFpxDowntimes(Downtime\Service $service)
    {
        $input = Request::all();

        $data = $service->createFpxDowntimes($input);

        return ApiResponse::json($data);
    }


    /**
     * @param $payment Payment\Entity
     * @param $mode string
     * @param $input array
     * @return bool
     */
    private function shouldSkipUpiICICICallback(Payment\Entity $payment, string $mode, array $input)
    {
        /*
         * Temporary Solution to Handle UPI BT cases to maintain payment in created state and reduce force authorized cases.
         * Long term fix is to have PENDING state which is being worked upon
         * */

        if ((isset($payment) === false) or
            (isset($mode) === false) or
            (isset($input) === false))
        {
            return false;
        }

        // only in case of UPI, proceed
        if (($payment->isBharatQr() === true) or
            ($payment->isUpiQr() === true) or
            ($payment->isRecurring() === true) or
            ($payment->isUpiTransfer() === true) or
            ($payment->isUpiOtm() === true))
        {
            return false;
        }

        // if gateway is not upi_icici, return
        if ($payment['gateway'] !== Gateway::UPI_ICICI)
        {
            return false;
        }

        // if success is false, only then the error block is populated
        if ((isset($input['success']) === true) and
            ($input['success'] === true))
        {
            return false;
        }

        // only in case of upi_icici and BT call RazorX
        if ((isset($input["error"]) === true) and
            (isset($input["error"]["gateway_error_code"]) === true) and
            ($input["error"]["gateway_error_code"] === "BT"))
        {
            $merchantID = $payment['merchant_id'];

            $variant = $this->app['razorx']->getTreatment($merchantID,
                RazorxTreatment::SKIP_UPI_ICICI_CALLBACK_FOR_BT,
                $mode);

            $this->trace->info(
                TraceCode::RAZORX_SKIP_UPI_ICICI_CALLBACK_FOR_BT,
                [
                    'variant' => $variant,
                    'mode' => $mode,
                    'merchant_id' => $merchantID
                ]);

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }

        return false;
    }
}
