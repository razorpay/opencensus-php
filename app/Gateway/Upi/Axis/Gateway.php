<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use RZP\Trace\TraceCode;
use RZP\Error;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;


class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER      = 'axis';

    const BANK          = 'axis';

    const TIMEOUT       = 20;

    /**
     * @var Crypto
     */
    protected $aesCrypto;

    protected $response;

    protected $gateway  = Payment\Gateway::UPI_AXIS;

    protected $map = [
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
        Fields::GATEWAY_TRANSACTION_ID  => Entity::GATEWAY_PAYMENT_ID,
        Fields::CODE                    => Entity::STATUS_CODE,
        Fields::GATEWAY_RESPONSE_CODE   => Entity::STATUS_CODE,
        Fields::W_COLLECT_TXN_ID        => Entity::NPCI_TXN_ID,
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
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $token = $this->fetchToken($input, Action::COLLECT);

        // Putting token to input as we want to maintain consistency in collect request
        $input['gateway']['token'] = $token;

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST, [
            'gateway'           => $this->gateway,
            'payment_id'        => $input['payment']['id'],
            'terminal_id'       => $input['terminal']['id'],
            'token'             => $token,
        ]);

        parent::action($input, Action::AUTHORIZE);

        $request = $this->getCollectRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $collectResponse = $this->parseGatewayResponse($response->body, $input);

        $this->checkResponseStatus($collectResponse[Fields::CODE], Status::COLLECT_SUCCESS);

        $this->updateGatewayPaymentEntity($gatewayPayment, $collectResponse);

        return [
            'data'   => [
                'vpa'   => $this->getDefaultPayeeVpa()
            ]
        ];
    }

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
            ['response' => $response]);
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

        try
        {
            $content = $this->jsonToArray($responseBody);

            $trace['content'] = $content;

            $this->trace->info(TraceCode::GATEWAY_RESPONSE, $trace);

            return $content;
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::GATEWAY_RESPONSE, $trace);

            throw $exception;
        }
    }

    private function checkResponseStatus($status, string $successStatus)
    {
        if ($status !== $successStatus)
        {
            $errorCode = ErrorCodeMap::getApiErrorCode($status);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ErrorCodeMap::getResponseMessage($status));
        }
    }

    protected function getTokenRequestArray($input)
    {
        $payment = $input['payment'];

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

        return $request;
    }

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

    /**
     * This is same as the payment description, capped
     * to 50 characters
     *
     * @param array $input
     *
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $paymentDescription = $input['payment']['description'] ?? '';

        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;

        $description = trim($description);

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    // ************************* CALLBACK *********************/

    public function preProcessServerCallback($input): array
    {
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

        $content = $input['gateway'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, [
            'gateway'           => $this->gateway,
            'payment_id'        => $input['payment']['id'],
            'terminal_id'       => $input['terminal']['id'],
            'content'           => $content,
        ]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        assertTrue($input['payment']['id'] === $content[Fields::MERCHANT_TRANSACTION_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($content[Fields::TRANSACTION_AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($content[Fields::GATEWAY_RESPONSE_CODE], Status::CALLBACK_SUCCESS);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];

    }

    public function postProcessServerCallback($input)
    {
        return $this->getCallbackResponseArray($input['gateway']);
    }

    protected function getCallbackResponseArray($content)
    {
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

    protected function getPaymentVerifyRequestArray($input)
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

        $request['headers'] = [
            'Content-Type' => 'application/json'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'       => $request,
                'payment_id'    => $input['payment']['id'],
                'terminal_id'   => $input['terminal']['id'],
                'gateway'       => $this->gateway,
            ]);

        return $request;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, $input, Action::VERIFY);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
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

        $content[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($verify->payment, $content);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        // Gateway sends result in different positions based on the kind of error hence handling both
        $result = $content[Fields::DATA][0][Fields::RESULT] ?? $content[Fields::RESULT];

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

    public function refund(array $input)
    {

        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::REFUND);

        $refund = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getRefundRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request'   => $request,
            ]);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, $input, Action::REFUND);

        $response[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($refund, $response);
    }

    protected function getRefundRequestArray(array $input): array
    {
        $data = [
            Fields::MERCH_ID            => $this->getMerchantId(),
            Fields::MERCH_CHAN_ID       => $this->getMerchantId2(),
            Fields::TXN_REFUND_ID       => $this->getRefundId($input['refund']),
            Fields::MOB_NO              => $this->getMobileNumber(),
            Fields::TXN_REFUND_AMOUNT   => $this->formatAmount($input['refund']['amount']),
            Fields::UNQ_TXN_ID          => $input['payment']['id'],
            Fields::REFUND_REASON       => $this->getRefundRemark($input),
            Fields::S_ID                => '',
        ];

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

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = [
            'Content-Type' => 'application/json',
        ];

        return $request;
    }

    public function getSecret()
    {
        return $this->config['aes_encryption_key'];
    }
}
