<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Gateway\Upi\Base;
use RZP\Models\BankAccount;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Upi\Axis\ErrorCodes\ErrorCodes;


class Gateway extends Base\Gateway
{
    use AuthorizeFailed;
    use CommonGatewayTrait;

    const ACQUIRER      = 'axis';

    const BANK          = 'axis';

    const TIMEOUT       = 20;

    const MAX_RETRY_COUNT = 5;

    /**
     * @var AESCrypto
     */
    protected $aesCrypto;

    protected $response;

    protected $gateway  = Payment\Gateway::UPI_AXIS;

    protected $map = [
        Entity::PAYMENT_ID              => Entity::PAYMENT_ID,
        Entity::VPA                     => Entity::VPA,
        Entity::RECEIVED                => Entity::RECEIVED,
        Entity::EXPIRY_TIME             => Entity::EXPIRY_TIME,
        Entity::TYPE                    => Entity::TYPE,
        Fields::UNQ_TXN_ID              => Entity::PAYMENT_ID,
        Fields::AMOUNT                  => Entity::AMOUNT,
        Fields::MERCH_ID                => Entity::GATEWAY_MERCHANT_ID,
        Fields::EXPIRY                  => Entity::EXPIRY_TIME,
        Fields::CUSTOMER_VPA            => Entity::VPA,
        Fields::MOB_NO                  => Entity::CONTACT,
        Fields::TXN_REFUND_ID           => Entity::REFUND_ID,
        Fields::RRN                     => Entity::NPCI_REFERENCE_ID,
        Fields::GATEWAY_TRANSACTION_ID  => Entity::NPCI_TXN_ID,
        Fields::CODE                    => Entity::STATUS_CODE,
        Fields::GATEWAY_RESPONSE_CODE   => Entity::STATUS_CODE,
        Fields::W_COLLECT_TXN_ID        => Entity::NPCI_TXN_ID,
        Entity::MERCHANT_REFERENCE      => Entity::MERCHANT_REFERENCE,
        Fields::CALLBACK_MERCHANT_ID    => Entity::GATEWAY_MERCHANT_ID,
        Fields::CHECK_STATUS_REF_ID     => Entity::NPCI_REFERENCE_ID,
        Fields::CHECK_STATUS_DEBIT_VPA  => Entity::VPA
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param array $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::action($input, GatewayBase\Action::AUTHENTICATE);

        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $request = $this->getAuthorizeRequestArray($input);

        $this->disableRetryForAction();

        $response = $this->sendGatewayRequest($request);

        $collectResponse = $this->parseGatewayResponse($response->body, $input);

        $collectResponse[Fields::W_COLLECT_TXN_ID] = $collectResponse[Fields::DATA][Fields::W_COLLECT_TXN_ID];

        $this->checkResponseStatus($collectResponse[Fields::CODE], Status::COLLECT_SUCCESS,
            $collectResponse);

        $this->updateGatewayPaymentEntity($gatewayPayment, $collectResponse);

        return [
            'data'   => [
                'vpa'   => $this->getDefaultPayeeVpa()
            ]
        ];
    }

    protected function authorizeIntent(array $input)
    {
        $attributes = [
            Entity::TYPE => Base\Type::PAY,
        ];

        $payment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $request = $this->getPayAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseGatewayResponse($response->body, $input);

        $this->updateGatewayPaymentEntity($payment, $content);

        if ((isset($content[Fields::CODE])) and
            (isset($content[Fields::DATA])) and
            ($content[Fields::CODE] == Status::TOKEN_SUCCESS))
        {
            return $this->getIntentRequest($input, $content);
        }

        throw new Exception\GatewayErrorException(
            Error\ErrorCode::GATEWAY_ERROR_VALIDATION_ERROR,
            null,
            null,
            [
                'response'     => $content,
                'gateway'      => $this->gateway,
                'payment_id'   => $input['payment']['id'],
            ],
            null,
            Action::AUTHENTICATE,
            true);
    }

    /**
     * This method will only be used for collect payments, to generate a token that will be passed
     * in collect request.
     * @deprecated - This function is deprecated in favour of single collect api
     */
    protected function fetchToken($input, string $action)
    {
        parent::action($input, Action::FETCH_TOKEN);

        $request =  $this->getTokenRequestArray($input);

        $request['headers'] = [
            'Content-Type' => 'application/json',
        ];

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, $input);

        if ((isset($response[Fields::CODE])) and
            (isset($response[Fields::DATA])) and
            ($response[Fields::CODE] == Status::TOKEN_SUCCESS))
        {
            return $response[Fields::DATA];
        }

        throw new Exception\GatewayErrorException(
            Error\ErrorCode::GATEWAY_ERROR_TOKEN_NOT_FOUND,
            null,
            null,
            ['response' => $response],
            null,
            Action::AUTHENTICATE,
            true);
    }

    public function validatePush($input)
    {
        parent::action($input, Action::VALIDATE_PUSH);

        // It checks if pre process happened through common gateway trait contracts
        if ((isset($input['data']['version']) === true) and
            ($input['data']['version'] === 'v2'))
        {
            $this->upiIsDuplicateUnexpectedPayment($input);

            $this->isValidUnexpectedPayment($input['data']['meta']['response']['plain']);

            return ;
        }

        if ((empty($input['meta']['version']) === false) and
            ($input['meta']['version'] === 'api_v2'))
        {
            $this->isDuplicateUnexpectedPaymentV2($input);

            $this->isValidUnexpectedPaymentV2($input);

            return;
        }

        $this->isDuplicateUnexpectedPayment($input);

        $this->isValidUnexpectedPayment($input);
    }

    /**
     * Check if its duplicate unexpected payment sent from art request
     * @param array $callbackData
     * @throws Exception\LogicException
     */
    protected function isDuplicateUnexpectedPaymentV2(array $callbackData)
    {
        $rrn = $callbackData['upi']['npci_reference_id'];

        $gateway = $callbackData['terminal']['gateway'];

        $upiEntity = $this->repo->fetchByNpciReferenceIdAndGateway($rrn, $gateway);

        if (empty($upiEntity) === false)
        {
            if ($upiEntity->getAmount() === (int) ($callbackData['payment']['amount']))
            {
                throw new Exception\LogicException(
                    'Duplicate Unexpected payment with same amount',
                    null,
                    [
                        'callbackData' => $callbackData
                    ]
                );
            }
        }
    }

    /**
     * Check if its a valid Unexpected Payment
     * @param array $callbackData
     * @throws Exception\LogicException
     * @throws GatewayErrorException
     */
    protected function isValidUnexpectedPaymentV2(array $callbackData)
    {
        $input = [
            'payment' => [
                'id'  => $callbackData['upi']['merchant_reference'],
            ],
        ];

        $gatewayPayment = [
            Entity::TYPE => Base\Type::PAY
        ];

        $this->action = Action::VERIFY;

        $request = $this->getPaymentVerifyRequestArrayV2($input, $gatewayPayment);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->action = Action::VALIDATE_PUSH;

        //Skips verify condition when we recieve ML01 which is for multiple order ids found.
        //This is when we have multiple RRN case.
        //This can be removed once we send rrn also in verify request.
        if (isset($content[Fields::DATA][0][Fields::CODE]) and $content[Fields::DATA][0][Fields::CODE] === "ML01")
        {
            $this->trace->info(TraceCode::UPI_AXIS_SKIP_VERIFY_FOR_MULTIPLE_ORDER_IDS,
                [
                    'merchant_reference' => $callbackData['upi']['merchant_reference'],
                    'npci_reference_id'  => $callbackData['upi']['npci_reference_id'],
                ]);

            return;
        }

        $result = ($content[Fields::DATA][0][Fields::RESULT] ?? ($content[Fields::RESULT] ?? null));

        $amount = ($content[Fields::DATA][0][Fields::AMOUNT] ?? ($content[Fields::AMOUNT] ?? null));

        $this->assertAmount(
            $this->formatAmount($callbackData[ConstantsEntity::PAYMENT][Payment\Entity::AMOUNT]),
            number_format($amount, 2, '.', ''));

        $this->checkResponseStatus(
            $result,
            [Status::VERIFY_DEEMED, Status::VERIFY_PENDING, Status::VERIFY_SUCCESS],
            $content);

    }

    protected function isDuplicateUnexpectedPayment($callbackData)
    {
        $merchantReference = $this->getPaymentIdFromServerCallback($callbackData);

        $gatewayPayment = $this->repo->fetchByMerchantReference($merchantReference);

        if ($gatewayPayment !== null)
        {
            throw new Exception\LogicException(
                'Duplicate Gateway payment found',
                null,
                [
                    'content' => $callbackData
                ]);
        }
    }

    public function getParsedDataFromUnexpectedCallback($callbackData)
    {
        if ((isset($callbackData['data']['version']) === true)
            and ($callbackData['data']['version']) === 'v2')
        {
            return $this->upiGetParsedDataFromUnexpectedCallback($callbackData);
        }

        $payment = [
            'method'   => 'upi',
            'amount'   => $this->getIntegerFormattedAmount($callbackData[Fields::TRANSACTION_AMOUNT]),
            'currency' => 'INR',
            'vpa'      => $callbackData[Fields::CUSTOMER_VPA],
            'contact'  => '+919999999999',
            'email'    => 'void@razorpay.com',
        ];

        $callbackMerchantId = $callbackData[Fields::CALLBACK_MERCHANT_ID];

        $terminal = [
            'gateway_merchant_id' => $callbackMerchantId
        ];

        return [
            'payment'  => $payment,
            'terminal' => $terminal
        ];
    }

    protected function isValidUnexpectedPayment($callbackData)
    {
        //
        // Verifies if the payload specified in the server callback is valid.
        //
        $input = [
            'payment' => [
                'id'  => $callbackData[Fields::MERCHANT_TRANSACTION_ID],
            ],
        ];

        $this->action = Action::VERIFY;

        $gatewayPayment = [
            Entity::TYPE => Base\Type::PAY
        ];

        $request = $this->getPaymentVerifyRequestArray($input, $gatewayPayment);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->action = Action::VALIDATE_PUSH;

        $result = ($content[Fields::DATA][0][Fields::RESULT] ?? ($content[Fields::RESULT] ?? null));

        $amount = ($content[Fields::DATA][0][Fields::AMOUNT] ?? ($content[Fields::AMOUNT] ?? null));

        $this->assertAmount(
            $this->getIntegerFormattedAmount($callbackData[Fields::TRANSACTION_AMOUNT]),
            $this->getIntegerFormattedAmount($amount));

        $this->checkResponseStatus(
            $result,
            [Status::VERIFY_DEEMED, Status::VERIFY_PENDING, Status::VERIFY_SUCCESS],
            $content);
    }

    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;

        if ((empty($callbackData['meta']['version']) === false) and
            ($callbackData['meta']['version'] === 'api_v2'))
        {
            return $this->authorizePushV2($input);
        }

        // To handle if callback data was preprocessed through mozart config with v2 contracts
        if ((isset($callbackData['data']['version']) === true) and
            ($callbackData['data']['version'] === 'v2'))
        {
            return $this->upiAuthorizePush($input);
        }

        $gatewayInput = [
            'payment' => [
                'id'     => $paymentId,
                'vpa'    => $callbackData[Fields::CUSTOMER_VPA],
                'amount' => $this->getIntegerFormattedAmount($callbackData[Fields::TRANSACTION_AMOUNT]),
            ],
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = [
            Entity::TYPE                    => Base\Type::PAY,
            Entity::MERCHANT_REFERENCE      => $callbackData[Fields::MERCHANT_TRANSACTION_ID],
            Entity::RECEIVED                => 1,
            Entity::VPA                     => $callbackData[Fields::CUSTOMER_VPA],
        ];

        $attributes = array_merge($callbackData, $attributes);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $result = $callbackData[Fields::GATEWAY_RESPONSE_CODE];

        $this->checkResponseStatus($result, Status::CALLBACK_SUCCESS, $callbackData);

        return [
            'acquirer' => [
                Payment\Entity::VPA          => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16  => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    /**
     * AuthorizePushV2 is triggered for reconciliation happening via ART
     * @param array $input
     * @return array[]
     * @throws Exception\LogicException
     */
    public function authorizePushV2($input)
    {
        list($paymentId, $callbackData) = $input;

        $callbackData['payment']['id'] = $paymentId;

        $gatewayInput = [
            'payment' => [
                'id'     => $paymentId,
                'vpa'    => $callbackData['upi']['vpa'],
                'amount' => (int) ($callbackData['payment']['amount']),
            ],
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = array_merge([
                                      Entity::TYPE                => Base\Type::PAY,
                                      Entity::RECEIVED            => 1,
                                      Entity::GATEWAY_DATA        => [],
                                  ], $callbackData['upi']);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, null, false);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];

    }

    /**
     * We only store the VPA because the rest of the fields
     * are filled by the callback
     *
     * @param  array $input
     * @param string $action
     *
     * @return array
     */
    protected function getGatewayEntityAttributes(array $input, string $action = Action::AUTHORIZE)
    {
        $attrs = [
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::VPA                 => $input['payment']['vpa'],
            Entity::ACTION              => $action,
            Entity::TYPE                => Base\Type::COLLECT,
        ];

        if ($action === Action::REFUND)
        {
            $attrs[Entity::REFUND_ID] = $input['refund']['id'];
        }

        if ($action === Action::AUTHORIZE)
        {
            $attrs[Entity::EXPIRY_TIME] = $input['upi']['expiry_time'];
        }

        return $attrs;
    }

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (merchant id)
     */
    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getGatewayMerchantId();
        }

        return $this->config['test_merchant_id'];
    }

    /**
     * Merchant Channel ID
     * @return string (merchant id)
     */
    protected function getMerchantId2()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getGatewayMerchantId2();
        }

        return $this->config['test_merchant_id2'];
    }

    /**
    * This is what shows up as the payee
    * on the notification to the customer
    */
    protected function getDefaultPayeeVpa()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getVpa();
        }

        return $this->config['test_vpa'];
    }

    /**
     * Mobile number - being used as a unique customer id generated by Bank for Razorpay
     */
    protected function getMobileNumber()
    {
        return $this->config['mobile_no'];
    }

    /**
     * @param $responseBody
     * @param $input
     * @param string $type
     * @return array
     */
    protected function parseGatewayResponse($responseBody, $input)
    {
        $trace = [
            'gateway'           => $this->gateway,
            'action'            => $this->action,
            'payment_id'        => $input['payment']['id'],
            'terminal_id'       => $input['terminal']['id'],
            'body'              => $responseBody,
        ];

        return $this->parseResponse($responseBody, $trace);
    }

    private function checkResponseStatus($status, $successStatuses, $content)
    {
        $successStatuses = (array) $successStatuses;

        if (in_array($status, $successStatuses, true) === false)
        {
            $errorCode = ErrorCodes::getErrorCode($status, $content);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ErrorCodeMap::getResponseMessage($status));

            if ($this->action === Action::AUTHENTICATE)
            {
                $ex->markSafeRetryTrue();
            }

            throw $ex;
        }
    }

    /**
     * This method is responsible to generate token request array for fetching the token
     * to be passed in collect payments.
     * The end point and request body are different for tpv and non tpv token requests.
     * @deprecated - This function is deprecated in favour of single collect api
     */
    protected function getTokenRequestArray($input)
    {
        $payment = $input['payment'];

        if ($input['merchant']->isTPVRequired() === true)
        {
            $request = $this->getTokenRequestForTpv($input, $payment);
        }
        else
        {
            $data = [
                Fields::MERCH_ID        => $this->getMerchantId(),
                Fields::MERCH_CHAN_ID   => $this->getMerchantId2(),
                Fields::UNQ_TXN_ID      => $payment['id'],
                Fields::UNQ_CUST_ID     => $payment['id'],
                Fields::AMOUNT          => $this->formatAmount($payment['amount']),
                Fields::TXN_DTL         => $this->getPaymentRemark($input),
                Fields::CURRENCY        => Currency::INR,
                Fields::ORDER_ID        => $payment['id'],
                Fields::CUSTOMER_VPA    => $payment['vpa'],
                Fields::EXPIRY          => (string) $input['upi']['expiry_time'],
                Fields::S_ID            => '',
            ];

            $dataStr = implode('', $data);

            $checksum = $this->encrypt($dataStr);

            $data[Fields::CHECKSUM] = bin2hex($checksum);

            $content = json_encode($data);

            $request = $this->getStandardRequestArray($content);

            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_REQUEST,
                [
                    'content'           => $data,
                    'gateway'           => $this->gateway,
                    'payment_id'        => $payment['id'],
                    'terminal_id'       => $input['terminal']['id'],
                ]);
        }

        return $request;
    }

    /**
     * @deprecated - This function is deprecated in favour of single collect api
     */
    protected function getTokenRequestForTpv($input, $payment)
    {
        $data = [
            Fields::MERCH_ID        => $this->getMerchantId(),
            Fields::MERCH_CHAN_ID   => $this->getMerchantId2(),
            Fields::UNQ_TXN_ID      => $payment['id'],
            Fields::UNQ_CUST_ID     => $payment['id'],
            Fields::AMOUNT          => $this->formatAmount($payment['amount']),
            Fields::ACCOUNT_NUM     => $input['order']['account_number'],
            Fields::IFSC_CODE_TPV   => substr($input['order']['bank'], 0, 4),
            Fields::TXN_DTL         => $this->getPaymentRemark($input),
            Fields::CURRENCY        => Currency::INR,
            Fields::ORDER_ID        => $payment['id'],
            Fields::CUSTOMER_VPA    => $payment['vpa'],
            Fields::EXPIRY          => (string) $input['upi']['expiry_time'],
            Fields::S_ID            => '',
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        $data[Fields::ACCOUNT_NUM] = bin2hex($this->encrypt($input['order']['account_number']));

        $content = json_encode($data);

        $request = $this->getStandardRequestArray($content, 'post', 'FETCH_TOKEN_TPV');

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'content'           => $data,
                'gateway'           => $this->gateway,
                'payment_id'        => $payment['id'],
                'terminal_id'       => $input['terminal']['id'],
            ]);

        return $request;
    }

    /**
     * This method is responsible to generate request array for authorize action for
     * collect payments.
     * The request body is different for tpv and non tpv authorize requests.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        // callbackUrl field is mandatory but it is not actually used by the gateway for sending the callback
        $callbackUrl = route('gateway_payment_callback_post', ["gateway" => $this->gateway], true);

        $data = [
            strtolower(Fields::MERCH_ID)      => $this->getMerchantId(),
            strtolower(Fields::MERCH_CHAN_ID) => $this->getMerchantId2(),
            strtolower(Fields::UNQ_TXN_ID)    => $payment['id'],
            strtolower(Fields::UNQ_CUST_ID)   => $payment['id'],
            Fields::AMOUNT                    => $this->formatAmount($payment['amount']),
            strtolower(Fields::TXN_DTL)       => $this->getPaymentRemark($input),
            Fields::CURRENCY                  => Currency::INR,
            strtolower(Fields::ORDER_ID)      => $payment['id'],
            strtolower(Fields::CUSTOMER_VPA)  => $payment['vpa'],
            Fields::EXPIRY                    => (string) $input['upi']['expiry_time'],
            Fields::CALLBACK_URL              => $callbackUrl,
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[strtolower(Fields::CHECKSUM)] = bin2hex($checksum);

        if ($input['merchant']->isTPVRequired() === true)
        {
            $data[Fields::ACCOUNT_NUM] = bin2hex($this->encrypt($input['order']['account_number']));

            $data[Fields::IFSC_CODE_TPV] = substr($input['order']['bank'], 0, 4);
        }

        $content = json_encode($data);

        // To use single collect url fetch the url from AUTHENTICATE_V2 constant
        $type = $this->action . "_v2";

        $request = $this->getStandardRequestArray($content, 'post', $type);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'content'     => $data,
                'gateway'     => $this->gateway,
                'payment_id'  => $payment['id'],
                'terminal_id' => $input['terminal']['id'],
                'action'      => $type,
            ]);

        return $request;
    }

    /**
     * @deprecated - This function is deprecated in favour of single collect api
     */
    protected function getCollectRequestArray($input, $content = [], $method = 'post', $type = null)
    {
        $request = $this->getStandardRequestArray($content, $method, $type);

        // Token is created on runtime which can not be hardcoded
        // And it goes as the path param so we are appending
        $request['url'] .= $input['gateway']['token'];

        return $request;
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    // ************************* CALLBACK *********************/

    public function preProcessServerCallback($input): array
    {
        if ($this->shouldUseUpiPreProcess(Payment\Gateway::UPI_AXIS))
        {
            $data = [
                'payload'       => $input['data'],
                'gateway'       => Payment\Gateway::UPI_AXIS,
                'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
            ];

            return $this->upiPreProcess($data);
        }

        $encryptedmessage = str_replace('\n','',$input[Fields::DATA]);
        $aesdecrypted = $this->decryptAes($encryptedmessage);
        /**
         * Being done to avoid control character error in json_decode which is got when - UTF string from AES
         * decryption is being passed.
         */
        $aesdecrypted = preg_replace('/[[:cntrl:]]/', '', $aesdecrypted);

        try
        {
            return $this->jsonToArray($aesdecrypted);
        }
        catch(\Exception $e)
        {
            throw new \Exception('The JSON message could not be converted to an array');
        }
    }

    public function getPaymentIdFromServerCallback($input)
    {
        $version = $input['data']['version'] ?? '';

        if ($version === 'v2')
        {
            return $this->upiPaymentIdFromServerCallback($input);
        }

        if (isset($input[Fields::MERCHANT_TRANSACTION_ID]) === true)
        {
            return $input[Fields::MERCHANT_TRANSACTION_ID];
        }

        throw new Exception\GatewayErrorException(
            Error\ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT,
            null,
            null,
            ['input' => $input]);
    }

    /**
     * Handles the S2S callback
     * @param  array $input
     * @return boolean
     */
    public function callback(array $input): array
    {
        parent::callback($input);

        if ((isset($input['gateway']['data']['version']) === true) and
            ($input['gateway']['data']['version']) === 'v2')
        {
            $acquirerData = $this->upiCallback($input);

            // Some merchants onboarded on axis wants this field
            $acquirerData['acquirer'][Payment\Entity::REFERENCE1] = $input['gateway']['data']['upi'][Entity::NPCI_TXN_ID] ?? '';

            return $acquirerData;
        }

        $content = $input['gateway'];

        $traceContent = $this->maskUpiDataForTracing($content, [
            Entity::VPA => Fields::CUSTOMER_VPA,
        ]);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, [
            'gateway'           => $this->gateway,
            'payment_id'        => $input['payment']['id'],
            'terminal_id'       => $input['terminal']['id'],
            'content'           => $traceContent,
        ]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        assertTrue($input['payment']['id'] === $content[Fields::MERCHANT_TRANSACTION_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($content[Fields::TRANSACTION_AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($content[Fields::GATEWAY_RESPONSE_CODE], Status::CALLBACK_SUCCESS,
            $content);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
                Payment\Entity::REFERENCE1 => $gatewayPayment->getNpciTransactionId(),
            ]
        ];

    }

    public function postProcessServerCallback($input)
    {
        return $this->getCallbackResponseArray($input['gateway']);
    }

    protected function getCallbackResponseArray($content)
    {
        $version = $content['data']['version'] ?? '';

        if ($version === 'v2')
        {
            $content = $content['data'];

            return [
                Fields::CALLBACK_STATUS_CODE          => $content['upi']['status_code'],
                Fields::CALLBACK_STATUS_DESCRIPTION   => $content['meta']['response']['plain']['gatewayResponseMessage'],
                Fields::CALLBACK_TXN_ID               => $content['upi']['npci_txn_id'],
            ];
        }

        $data = [
            Fields::CALLBACK_STATUS_CODE          => $content[Fields::GATEWAY_RESPONSE_CODE],
            Fields::CALLBACK_STATUS_DESCRIPTION   => $content[Fields::GATEWAY_RESPONSE_MESSAGE],
            Fields::CALLBACK_TXN_ID               => $content[Fields::GATEWAY_TRANSACTION_ID],
        ];

        return $data;
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attributes = $this->getMappedAttributes($response);
        // To mark that we have received a response for this request
        $attributes[Entity::RECEIVED] = 1;

        $payment->fill($attributes);

        $payment->generatePspData($attributes);

        $this->repo->saveOrFail($payment);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // for barricade flow return gateway verify response
    public function verifyGateway(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify = $this->sendPaymentVerifyRequestGateway($verify);

        return $verify->getDataToTrace();
    }

    protected function runPaymentVerifyFlow($verify)
    {
        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify);

        if (($gatewayPayment === null) and
            ($this->shouldReturnIfPaymentNullInVerifyFlow($verify)))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'payment_id'  => $verify->input['payment']['id'],
                    'message'     => 'payment id not found in the gateway database',
                    'terminal_id' => $verify->input['terminal']['id'],
                    'gateway'     => $this->gateway,
                ]
            );

            return null;
        }

        $this->sendPaymentVerifyRequest($verify);

        $this->verifyPayment($verify);

        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id'    => $this->input['payment']['id'],
                    'gateway'       => $this->gateway,
                    'terminal_id'   => $this->input['terminal']['id'],
                ]
            );
        }

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        return $verify->getDataToTrace();
    }

    protected function getPaymentVerifyRequestArray($input, $gatewayPayment)
    {
        $payment = $input['payment'];

        $data = [
            Fields::CHECK_STATUS_MERCH_ID       => $this->getMerchantId(),
            Fields::CHECK_STATUS_MERCH_CHAN_ID  => $this->getMerchantId2(),
            Fields::CHECK_STATUS_UNQ_TXN_ID     => $payment['id'],
            Fields::CHECK_STATUS_MOBILE_NO      => $this->getMobileNumber(),
        ];

        if ($gatewayPayment[Entity::TYPE] === Base\Type::PAY)
        {
            list($data[Fields::CHECK_STATUS_MERCH_ID],
                $data[Fields::CHECK_STATUS_MERCH_CHAN_ID]) = $this->getAggregatorIds($this->terminal);
        }

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECK_STATUS_CHECKSUM] = bin2hex($checksum);

        $content = json_encode($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers']['Content-Type'] = 'application/json';

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'       => $request,
                'gateway'       => $this->gateway,
            ]);

        return $request;
    }

    protected function getPaymentVerifyRequestArrayV2($input, $gatewayPayment)
    {
        $payment = $input['payment'];

        $data = [
            Fields::CHECK_STATUS_MERCH_ID       => $this->getMerchantId(),
            Fields::CHECK_STATUS_MERCH_CHAN_ID  => $this->getMerchantId2(),
            Fields::CHECK_STATUS_UNQ_TXN_ID     => $payment['id'],
            Fields::CHECK_STATUS_MOBILE_NO      => $this->getMobileNumber(),
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECK_STATUS_CHECKSUM] = bin2hex($checksum);

        $content = json_encode($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers']['Content-Type'] = 'application/json';

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'       => $request,
                'gateway'       => $this->gateway,
            ]);

        return $request;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $request = $this->getPaymentVerifyRequestArray($input, $gatewayPayment);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, $input, Action::VERIFY);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function sendPaymentVerifyRequestGateway($verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $request = $this->getPaymentVerifyRequestArray($input, $gatewayPayment);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, $input, Action::VERIFY);

        $verify->verifyResponseContent = $content;

        return $verify;
    }

    protected function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $input = $verify->input;

        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

            $actualAmount = number_format($content[Fields::DATA][0][Fields::AMOUNT], 2, '.', '');

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $entity[Entity::RECEIVED] = 1;

        if (isset($content[Fields::DATA][0][Fields::CHECK_STATUS_DEBIT_VPA]))
        {
            $entity[Entity::VPA] = $content[Fields::DATA][0][Fields::CHECK_STATUS_DEBIT_VPA];
        }
        if (isset($content[Fields::DATA][0][Fields::CHECK_STATUS_REF_ID]))
        {
            $entity[Entity::NPCI_REFERENCE_ID] = $content[Fields::DATA][0][Fields::CHECK_STATUS_REF_ID];
        }
        if (isset($content[Fields::DATA][0][Fields::CHECK_STATUS_TXN_ID]))
        {
            $entity[Entity::NPCI_TXN_ID] = $content[Fields::DATA][0][Fields::CHECK_STATUS_TXN_ID];
        }

        // This will set the Provide and Bank
        $verify->payment->generatePspData($entity);

        // This will update VPA, NPCI_REFERENCE_ID(RRN) and RECEIVED on the entity.
        $this->updateGatewayPaymentEntity($verify->payment, $entity, false);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        // Gateway sends result in different positions based on the kind of error hence handling both
        // sometimes gateway is sending empty response
        $result = ($content[Fields::DATA][0][Fields::RESULT] ?? ($content[Fields::RESULT] ?? null));

        $verify->gatewaySuccess = ($result === Status::VERIFY_SUCCESS);
    }

    protected function getCipherInstance(): RSA
    {
        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    /**
     * Encrypts data before sending it to Axis
     * @param  string $data
     * @return string
     */
    protected function encrypt(string $data): string
    {
        $rsa = $this->getCipherInstance();

        $publickey = $this->config['public_key'];

        $publickey = str_replace('\n', '', $publickey);

        $rsa->loadKey($publickey);

        return $rsa->encrypt($data);
    }

    /**
     * @param array $input
     * @return array $scroogeResponse
     * @throws Exception\GatewayErrorException
     */
    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        if ($input['payment']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE)
        {
            $verifyRequestArray = $this->getVerifyRefundUpsRequestArray($input);
        } else
        {
            $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail($input['refund']['payment_id'], Action::AUTHORIZE);

            $verifyRequestArray = $this->getVerifyRefundRequestArray($input, $gatewayEntity);
        }

        $content = json_encode($verifyRequestArray);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'       => $request,
                'refund_id'     => $input['refund']['id'],
                'gateway'       => $this->gateway,
                'plain_data'    => $verifyRequestArray,
                'cps_route'     => $input['payment']['cps_route'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $responseContent = $this->parseVerifyRefundResponse($response->body, $input);

        return $this->checkRefundResponseStatus($responseContent);
    }

    public function parseVerifyRefundResponse($responseBody, $input)
    {
        $trace = [
            'gateway'           => $this->gateway,
            'action'            => $this->action,
            'payment_id'        => $input['refund']['id'],
            'terminal_id'       => $input['terminal']['id'],
        ];

        return $this->parseResponse($responseBody, $trace);
    }

    public function parseResponse($responseBody, array $trace)
    {
        $trace['error']     = null;
        $trace['action']    = $this->getAction();

        try
        {
            $content = $this->jsonToArray($responseBody);

            $trace['content'] = $this->maskUpiDataForTracing($content, [
                Entity::VPA => Fields::CHECK_STATUS_DEBIT_VPA,
            ]);

            $this->trace->info(TraceCode::GATEWAY_RESPONSE, $trace);

            return $content;
        }
        catch (Exception\RuntimeException $exception)
        {
            $trace['error'] = $exception->getMessage();

            $this->trace->error(TraceCode::GATEWAY_RESPONSE, $trace);

            // We need to suppress the server error as it's the gateway sending wrong response
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                null,
                $trace);
        }
    }

    public function getVerifyRefundRequestArray(array $input, Entity $gatewayEntity)
    {
        //
        // Appending (attempt count - 1)  to refund id for verifying previous refund if that was successful.
        // For scrooge refunds, attempts are sent from scrooge which signifies the attempts which have been done on
        // this. As attempts in scrooge starts with 0, For eg. if attempts = 5,
        // that means we will be requesting refund R5 and we need to verify for R4.
        //
        $attempts = $input['refund']['attempts'] - 1;

        //
        // If this is 0th or 1st attempt, verify refund should be called for first refund (exact Refund Id)
        // Appending empty string to refund if we want to verify refund with 14 digit refund id.
        //
        if (((int) $attempts === 0) or ((int) $input['refund']['attempts'] === 0))
        {
            $attempts = '';
        }

        $data = [
            Fields::MERCH_ID       => $this->getMerchantId(),
            Fields::MERCH_CHAN_ID  => $this->getMerchantId2(),
            Fields::UNQ_TXN_ID     => $this->getUniqueTransactionId($gatewayEntity),
            Fields::TXN_REFUND_ID  => $input['refund']['id'] . $attempts,
        ];

        if ($gatewayEntity->getType() === Base\Type::PAY)
        {
            list($data[Fields::MERCH_ID], $data[Fields::MERCH_CHAN_ID]) = $this->getAggregatorIds($this->terminal);
        }

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        return $data;
    }

    public function getVerifyRefundUpsRequestArray(array $input)
    {
        $payment = $input['payment'];

        $fiscalEntity = $this->app['upi.payments']->findByPaymentIdAndGatewayOrFail(
            $payment['id'],
            $payment['gateway'],
            [
                'merchant_reference',
                'flow'
            ]);

        //
        // Appending (attempt count - 1)  to refund id for verifying previous refund if that was successful.
        // For scrooge refunds, attempts are sent from scrooge which signifies the attempts which have been done on
        // this. As attempts in scrooge starts with 0, For eg. if attempts = 5,
        // that means we will be requesting refund R5 and we need to verify for R4.
        //
        $attempts = $input['refund']['attempts'] - 1;

        //
        // If this is 0th or 1st attempt, verify refund should be called for first refund (exact Refund Id)
        // Appending empty string to refund if we want to verify refund with 14 digit refund id.
        //
        if (((int) $attempts === 0) or ((int) $input['refund']['attempts'] === 0))
        {
            $attempts = '';
        }

        $data = [
            Fields::MERCH_ID       => $this->getMerchantId(),
            Fields::MERCH_CHAN_ID  => $this->getMerchantId2(),
            Fields::UNQ_TXN_ID     => $fiscalEntity['merchant_reference'] ?? $payment['id'],
            Fields::TXN_REFUND_ID  => $input['refund']['id'] . $attempts,
        ];

        if ($fiscalEntity['flow'] === Base\Type::INTENT)
        {
            list($data[Fields::MERCH_ID], $data[Fields::MERCH_CHAN_ID]) = $this->getAggregatorIds($this->terminal);
        }

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        return $data;
    }

    private function checkRefundResponseStatus($responseContent)
    {
        $scroogeResponse = new GatewayBase\ScroogeResponse();

        $scroogeResponse->setGatewayVerifyResponse($responseContent)
                        ->setGatewayKeys($this->getGatewayData($responseContent));

        $code = $responseContent[Fields::CODE];

        if ($code === Status::REFUND_SUCCESS)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        if ($code === Status::REFUND_ABSENT)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(Error\ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }

        $errorCode = ErrorCodes::getErrorCode($code, $responseContent);

        throw new Exception\GatewayErrorException(
            $errorCode,
            $code,
            ErrorCodeMap::getResponseMessage($code),
            [
                Payment\Gateway::GATEWAY_VERIFY_RESPONSE => json_encode($responseContent),
                Payment\Gateway::GATEWAY_KEYS            => $this->getGatewayData($responseContent)
            ]);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::REFUND);

        if (($input['payment']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE)||
            ($input['payment']['cps_route'] === Payment\Entity::REARCH_UPI_PAYMENT_SERVICE))
        {
            $payment = $input['payment'];

            $fiscalEntity = $this->app['upi.payments']->findByPaymentIdAndGatewayOrFail(
                $payment['id'],
                $payment['gateway'],
                [
                    'merchant_reference',
                    'flow'
                ]);

            $attributes[Entity::TYPE] = $fiscalEntity['flow'];

            $refund = $this->createGatewayPaymentEntity($attributes);

            $request =  $this->getUpsRefundRequestArray($input, $fiscalEntity);
        } else
        {
            $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

            $attributes[Entity::TYPE] = $gatewayEntity->getType();

            $refund = $this->createGatewayPaymentEntity($attributes);

            $request =  $this->getRefundRequestArray($input, $gatewayEntity);
        }

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request'   => $request,
                'cps_route' => $input['payment']['cps_route'],
            ]);

        try
        {
            $response = $this->sendGatewayRequest($request);

            $response = $this->parseGatewayResponse($response->body, $input, Action::REFUND);

            $response[Entity::RECEIVED] = 1;

            $this->updateGatewayPaymentEntity($refund, $response);

            $this->checkRefundStatus($response);
        }
        catch(\Throwable $e)
        {
            if ($e->getCode() === Error\ErrorCode::SERVER_ERROR_RUNTIME_ERROR)
            {
                throw new Exception\RuntimeException(
                    $e->getMessage(),
                    $e->getData(), null, Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);
            }

            throw $e;
        }

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
        ];
    }

    protected function checkRefundStatus($response)
    {
        if ($response[Fields::CODE] != Status::REFUND_SUCCESS)
        {
            $code = $response[Fields::CODE];

            $errorCode = ErrorCodes::getErrorCode($code, $response);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $code,
                ErrorCodeMap::getResponseMessage($code),
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
                ]);
        }
    }

    protected function getExternalMockUrl(string $type)
    {
        return  env('EXTERNAL_MOCK_GO_GATEWAY_DOMAIN') . '/upi_axis' . $this->getRelativeUrl($type);
    }

    protected function getRefundRequestArray(array $input, Entity $gatewayEntity): array
    {
        $data = [
            Fields::MERCH_ID            => $this->getMerchantId(),
            Fields::MERCH_CHAN_ID       => $this->getMerchantId2(),
            Fields::TXN_REFUND_ID       => $this->getRefundId($input['refund']),
            Fields::MOB_NO              => $this->getMobileNumber(),
            Fields::TXN_REFUND_AMOUNT   => $this->formatAmount($input['refund']['amount']),
            Fields::UNQ_TXN_ID          => $this->getUniqueTransactionId($gatewayEntity),
            Fields::REFUND_REASON       => $this->getRefundRemark($input),
            Fields::S_ID                => '',
        ];

        if ($gatewayEntity->getType() === Base\Type::PAY)
        {
            list($data[Fields::MERCH_ID], $data[Fields::MERCH_CHAN_ID]) = $this->getAggregatorIds($this->terminal);
        }

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        $content = json_encode($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'content'           => $data,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'refund_id'         => $input['refund']['id'],
                'terminal_id'       => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function getUpsRefundRequestArray(array $input, array $fiscalEntity): array
    {
        $payment = $input['payment'];

        $data = [
            Fields::MERCH_ID            => $this->getMerchantId(),
            Fields::MERCH_CHAN_ID       => $this->getMerchantId2(),
            Fields::TXN_REFUND_ID       => $this->getRefundId($input['refund']),
            Fields::MOB_NO              => $this->getMobileNumber(),
            Fields::TXN_REFUND_AMOUNT   => $this->formatAmount($input['refund']['amount']),
            Fields::UNQ_TXN_ID          => $fiscalEntity['merchant_reference'] ?? $payment['id'],
            Fields::REFUND_REASON       => $this->getRefundRemark($input),
            Fields::S_ID                => '',
        ];

        if ($fiscalEntity['flow'] === Base\Type::INTENT)
        {
            list($data[Fields::MERCH_ID], $data[Fields::MERCH_CHAN_ID]) = $this->getAggregatorIds($this->terminal);
        }

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        $content = json_encode($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'content'           => $data,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'refund_id'         => $input['refund']['id'],
                'terminal_id'       => $input['terminal']['id'],
            ]);

        return $request;
    }

    /**
     * This is done in order to fix duplicate
     * merchant transaction id issue in case
     * refund is retried multiple times
     *
     * @return string
     */
    protected function getRefundId(array $refund)
    {
        return $refund['id'] . ($refund['attempts'] ?: '');
    }

    protected function getUniqueTransactionId(Entity $gatewayPayment)
    {
        $uniqueTxnId = $gatewayPayment->getMerchantReference() ?? $gatewayPayment->getPaymentId();

        return $uniqueTxnId;
    }

    /**
     * Returns a refund description, capped to 50 chars
     * @param  array  $input
     * @return string
     */
    protected function getRefundRemark(array $input): string
    {
        $description = $input['merchant']->getFilteredDba();

        $description = $description ?: 'Razorpay';

        return 'Refund for ' . substr($description, 0, 36);
    }

    protected function createCryptoIfNotCreated()
    {
        $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $this->getSecret());
    }

    public function decryptAes(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_ENCODING, null);
    }

    protected function getRequestOptions()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        $options = [
            'hooks' => $hooks
        ];

        return $options;
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers']['Content-Type'] = 'application/json';

        $request['options'] = $this->getRequestOptions();

        return $request;
    }

    public function getSecret()
    {
        return $this->config['aes_encryption_key'];
    }

    protected function getPayAuthorizeRequestArray(array $input)
    {
        $payment = $input['payment'];

        $data = [
            Fields::MERCH_CHAN_ID => $this->getMerchantId2(),
            Fields::ORDER_ID      => $payment['id'],
            Fields::CREDIT_VPA    => $this->getMerchantVpa(),
        ];

        $dataStr = implode('', $data);

        $checksum = $this->encrypt($dataStr);

        $data[Fields::CHECKSUM] = bin2hex($checksum);

        $path = 'pay';

        if ($input['merchant']->isTPVRequired() === true)
        {
            $data[Fields::ACCOUNT_NUM_TPV]    =  $input['order'][Order\Entity::ACCOUNT_NUMBER];
            $data[Fields::IFSC_CODE_TPV]      =  substr($input['order'][Order\Entity::BANK], 0, 4);
            $path = 'pay_v2';
        }

        $content = json_encode($data);

        $request = $this->getStandardRequestArray($content, 'post', $path);

        $traceData = $this->maskUpiDataForTracing($request, [
            Entity::VPA => Fields::CUSTOMER_VPA,
        ]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'content'           => $traceData,
                'gateway'           => $this->gateway,
                'payment_id'        => $payment['id'],
                'terminal_id'       => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function getMerchantVpa()
    {
        return $this->terminal->getVpa();
    }

    protected function getIntentRequest($input, $response)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $this->getMerchantVpa(),
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '',
                                                             $input['merchant']->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => $response[Fields::DATA],
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => $input['payment']['currency'],
            Base\IntentParams::MCC           => $this->getMerchantCategory($input),
        ];

        if (isset($input['upi']['reference_url']) === true)
        {
            $content[Base\IntentParams::URL] = $input['upi']['reference_url'];
        }

        return ['data' => ['intent_url' => $this->generateIntentString($content)]];
    }

    /**
     * This function authorize the payment forcefully when verify api is not supported
     * or not giving correct response.
     *
     * @param $input
     * @return bool
     */
    public function forceAuthorizeFailed(array $input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        /**
         * We do not update the upi status code on callback, thus we are going to
         * use success as status code to make sure we do not force auth already auth txns.
         */
        if (($gatewayPayment[Entity::STATUS_CODE] === Status::COLLECT_SUCCESS) and
            ($gatewayPayment[Entity::RECEIVED] === true))
        {
            return true;
        }

        $attr = [];

        if ((empty($input['gateway']['meta']['version']) === false) and
            ($input['gateway']['meta']['version'] === 'api_v2'))
        {
            $attr = [
                Entity::NPCI_REFERENCE_ID => $input['gateway']['upi']['npci_reference_id'],
                Entity::VPA               => $input['gateway']['upi'][Entity::VPA],
            ];

        }
        else
        {
            $attr = [
                Entity::NPCI_REFERENCE_ID => $input['gateway'][Fields::RRN],
                Entity::VPA               => $input['gateway'][Entity::VPA],
            ];
        }

        $attr[Entity::STATUS_CODE] =  Status::COLLECT_SUCCESS;

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();

        return true;
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                Fields::CODE   => $refundFields[Fields::CODE] ?? null,
                Fields::RESULT => $refundFields[Fields::RESULT] ?? null,
            ];
        }

        return [];
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string, default value is 5411
     */
    protected function getMerchantCategory(array $input)
    {
        return $input['merchant']['category'] ?? '5411';
    }

    protected function getPaymentRemark(array $input)
    {
        if ($input['payment']['merchant_id'] === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $description = $input['payment']['description'];

            $filteredDescription = Payment\Entity::getFilteredDescription($description);

            return substr($filteredDescription, 0, 50);
        }

        return parent::getPaymentRemark($input);
    }

    protected function getAggregatorIds($terminal)
    {
        if (($terminal[Terminal\Entity::GATEWAY_TERMINAL_ID] !== null) and
            ($terminal[Terminal\Entity::GATEWAY_ACCESS_CODE] !== null))
        {
            return [$terminal[Terminal\Entity::GATEWAY_TERMINAL_ID], $terminal[Terminal\Entity::GATEWAY_ACCESS_CODE]];
        }
        else
        {
            return [$this->config['live_razorpay_merchant_id'], $this->config['live_razorpay_merchant_channel_id']];
        }
    }

    public function syncGatewayTransactionDataFromCps(array $attributes, array $input)
    {
        $gatewayEntity = $this->repo->findByPaymentIdAndAction($attributes[Entity::PAYMENT_ID], $input[Entity::ACTION]);

        if (empty($gatewayEntity) === true)
        {
            $gatewayEntity = $this->createGatewayPaymentEntity($attributes, $input[Entity::ACTION]);
        }

        $gatewayEntity->setAction($input[Entity::ACTION]);

        $this->updateGatewayPaymentEntity($gatewayEntity, $attributes, false);
    }
}
