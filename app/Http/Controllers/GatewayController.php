<?php

namespace RZP\Http\Controllers;

use Request;
use Redirect;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Admin;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Gateway\Base\Action;
use RZP\Base\RuntimeManager;
use RZP\Models\Gateway\Rule;
use RZP\Models\Payment\Gateway;
use Exception as BaseException;
use RZP\Models\Gateway\Downtime;
use RZP\Gateway\Mozart as Mozart;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Jobs\DynamicNetBankingUrlUpdater;
use RZP\Gateway\Netbanking\Base\Repository;
use RZP\Gateway\Enach\Npci\Netbanking as EnachNb;
use RZP\Models\Gateway\Priority as GatewayPriority;
use RZP\Gateway\Wallet\Amazonpay\ResponseFields as AmazonResponse;
use RZP\Models\Gateway\Downtime\Webhook\Constants\Vajra as VajraConstants;

class GatewayController extends Controller
{

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
        $gateway = $this->app['gateway']->gateway($gatewayDriver);

        // Some gateways may need some pre-processing on the input
        // to be able to call the next few methods.
        //
        // Eg: gateway request needs to be decrypted, this shouldn't be direct method call
        // TODO: change this to utilize callGatewayFunction
        $input = $gateway->preProcessServerCallback($input, $gatewayDriver);

        // TODO: this should also utilize callGatewayFunction, although we should have
        // used preProcessServerCallback itself to return it in some way
        $paymentId = $gateway->getPaymentIdFromServerCallback($input, $gatewayDriver);

        $paymentRepo = $this->app['repo']->payment;

        // This is hackish, we find mode based on searching in both DB's
        $mode = $paymentRepo->determineLiveOrTestModeForEntityWithGateway($paymentId, $gatewayDriver);

        if ($mode === null)
        {
            return (new Payment\Service)->unexpectedCallback($input, $paymentId, $gatewayDriver);
        }
        else
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            $paymentId = Payment\Entity::getSignedId($paymentId);

            return (new Payment\Service)->s2sCallback($paymentId, $input);
        }
    }

    protected function processServerCallbackWithGatewayResponse($input, $gatewayDriver)
    {
        $gateway = $this->app['gateway']->gateway($gatewayDriver);

        $input = $gateway->preProcessServerCallback($input);

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $paymentRepo = $this->app['repo']->payment;

        $mode = $paymentRepo->determineLiveOrTestModeForEntityWithGateway($paymentId, $gatewayDriver);

        $postInput = [
            'gateway' => $input,
        ];

        try
        {
            if ($mode === null)
            {
                $data = (new Payment\Service)->unexpectedCallback($input, $paymentId, $gatewayDriver);
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
                    $data = (new Payment\Service)->s2sCallback($paymentId, $input);
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

    public function callbackGateway($gateway)
    {
        $input = Request::all();

        $data = [];

        $trace = $this->app['trace'];

        $trace->info(
            TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK,
            [
                'input'     => $input,
                'body'      => Request::getContent(),
                'headers'   => Request::header(),
                'gateway'   => $gateway,
            ]);

        switch ($gateway)
        {
            // Standard Cases
            case Gateway::WALLET_FREECHARGE:
            case Gateway::BILLDESK:
            case Gateway::NETBANKING_AXIS:
            case Gateway::UPI_AIRTEL:
            case Gateway::WALLET_PHONEPE:
            case Gateway::UPI_CITI:
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

            //Special case because gateway is upi_mindgate
            case 'upi_hdfc':
                $data = $this->processServerCallbackWithGatewayResponse($input, Payment\Gateway::UPI_MINDGATE);
                break;

            // Special case because we need the raw request body
            case Gateway::UPI_RBL:
                $input = Request::getContent();
                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);
                break;

            case Gateway::UPI_ICICI:
                $input = Request::getContent();

                $data = $this->processServerCallback($input, $gateway);

                break;

            case Gateway::UPI_JUSPAY:

                $input = [
                    'headers' => [
                        'x-merchant-payload-signature' => Request::header('X-Merchant-Payload-Signature')
                    ],
                    'raw'     => Request::getContent(),
                    'body'    => $input,
                ];

                $data = $this->processServerCallback($input, $gateway);

                break;

            case Gateway::UPI_HULK:
                $input['headers'] = Request::header();
                $input['raw'] = Request::getContent();

                $data = $this->processServerCallback($input, Gateway::UPI_HULK);

                break;

            case Gateway::UPI_MINDGATE:
            case Gateway::UPI_SBI:
            case Gateway::UPI_AXIS:
                $data = $this->processServerCallbackWithGatewayResponse($input, $gateway);
                break;

        }

        // $input['gateway'] = $gateway;

        return ApiResponse::json($data);
    }

    public function staticCallbackGateway($method, $gateway, $mode)
    {
        $input = Request::all();

        $data = [];

        $trace = $this->app['trace'];

        $trace->info(
            TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK,
            [
                'input'     => $input,
                'body'      => Request::getContent(),
                'headers'   => Request::header(),
                'gateway'   => $gateway,
            ]);

        switch ($method)
        {
            case Payment\Method::NETBANKING:
                $data = $this->staticCallbackNetbanking($input, $gateway,$mode);
                return  $data;
        }

        return null;
    }

    public function staticCallbackNetbanking($input, $gateway, $mode)
    {
        switch ($gateway)
        {
            case Gateway::NETBANKING_KVB:
            $data = $this->callbackKvbbank($input, $mode);
            return $data;
        }

        return null;
    }

    public function callbackKotakCancel()
    {
        return $this->callbackKotak();
    }

    public function callbackKotak()
    {
        $inputMsg = Request::get('msg');
        $input = explode('|', $inputMsg);

        $app = \App::getFacadeRoot();

        $result = $this->getNetbankingEntityAndModeByTraceId($input[3]);

        $nb = $result['nb'];

        $mode = $result['mode'];

        $trace = $app['trace'];

        // check mode before search
        $trace->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input_all' => Request::all(),
                'input_msg' => Request::input('msg'),
                'input_arr' => $input
            ]);

        if ($nb === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite trace id: ' . $input[3]);
        }

        $paymentId = $nb->getPaymentId();
        $publicPaymentId = $nb->getPublicPaymentId();

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $publicKey = $this->getMerchantKeyForPayment($payment, $mode);

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $url = $url . '?msg=' . $inputMsg;

        return Redirect::to($url);
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
        $input = Request::all();

        $this->app['trace']->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input'   => $input ,
                'gateway' => 'netbanking_canara'
            ]
        );

        $gateway = $this->app['gateway']->gateway(Gateway::NETBANKING_CANARA);

        $input = $gateway->preProcessServerCallback($input);

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        $this->app['config']->set('database.default', $mode);

        $netbanking = $this->app['repo']->netbanking->findByPaymentIdAndAction(
            $paymentId,
            \RZP\Gateway\Base\Action::AUTHORIZE
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

    public function callbackKvbbank($input, $mode)
    {
        $this->app['trace']->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'gateway'          => 'netbanking_kvb',
                'encrypted_string' => $input
            ]
        );

        $gateway = $this->app['gateway']->gateway(Gateway::NETBANKING_KVB);

        $response = $gateway->preProcessServerCallback($input, Gateway::NETBANKING_KVB, $mode);

        $paymentId = $gateway->getPaymentIdFromServerCallback($response, Gateway::NETBANKING_KVB);

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
        return $this->postGatewayDowntimeWebhook($service, Downtime\Source::VAJRA);
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

    public function createGatewayPriority(string $method)
    {
        $input = Request::all();

        $data = (new GatewayPriority\Service)->createPriorityForMethod($method, $input);

        return ApiResponse::json($data);
    }

    public function getGatewayPriority()
    {
        $data = (new GatewayPriority\Service)->fetchPriority();

        return ApiResponse::json($data);
    }

    public function addOrUpdateGatewayPriority(string $method)
    {
        $input = Request::all();

        $data = (new GatewayPriority\Service)->addOrUpdatePriorityForMethod($method, $input);

        return ApiResponse::json($data);
    }

    public function removeGatewayPriority(string $method)
    {
        $input = Request::all();

        $data = (new GatewayPriority\Service)->removePriorityForMethod($method, $input);

        return ApiResponse::json($data);
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

    protected function getMerchantKeyForPayment(Payment\Entity $payment, string $mode)
    {
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

    protected function statsGatewayDowntimeDetection(Downtime\Service $service)
    {
        $data = $service->stats();

        return ApiResponse::json([
            'stats' => $data
        ]);
    }
}
