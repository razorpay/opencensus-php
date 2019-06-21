<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BharatQr;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Gateway\Upi\Base\UpiErrorCodes;
use RZP\Models\Terminal;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = 'hdfc';

    protected $gateway = Payment\Gateway::UPI_MINDGATE;

    protected $response;

    const BANK = 'hdfc';

    const TIMEOUT = 20;

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpaypg@hdfcbank';

    // Transaction Types
    const P2P = 'P2P';
    const P2M = 'P2M';

    const PAY = 'PAY';

    const FIELD_LENGTH = [
        Action::AUTHENTICATE  => 17,
        Action::AUTHORIZE     => 17,
        Action::VALIDATE_VPA  => 14,
        Action::REFUND        => 20,
        Action::VERIFY        => 14,
        Action::VALIDATE_PUSH => 14,
        Action::INTENT_TPV    => 19,
    ];

    protected $map = [
        Entity::VPA                       => Entity::VPA,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::TYPE                      => Entity::TYPE,
        ResponseFields::PAYER_VA          => Entity::VPA,
        ResponseFields::PAYER_NAME        => Entity::NAME,
        ResponseFields::RESPCODE          => Entity::STATUS_CODE,
        // This is a 5 digit number that is the reference ID on the HDFC side
        ResponseFields::UPI_TXN_ID        => Entity::GATEWAY_PAYMENT_ID,
        // NPCI provided RRN for the transaction
        ResponseFields::NPCI_UPI_TXN_ID   => Entity::NPCI_REFERENCE_ID,
        ResponseFields::ACCOUNT_NUMBER    => Entity::ACCOUNT_NUMBER,
        ResponseFields::IFSC_CODE         => Entity::IFSC,
        Entity::MERCHANT_REFERENCE        => Entity::MERCHANT_REFERENCE,
    ];

    protected function getMaxRetryCount()
    {
        return 5;
    }

    /**
     * Authorizes a payment using UPI Gateway
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::action($input, Action::AUTHENTICATE);

        if ($this->isBharatQrPayment() === true)
        {
            $attributes = $this->getBharatqrGatewayAttributes($input);

            $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

            return null;
        }

        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            if ($input['merchant']->isTPVRequired() === true)
            {
                $this->initiateIntentTpv($input);
            }

            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->updateGatewayPaymentEntity($gatewayPayment, $response);

        $this->checkResponseStatus($response[ResponseFields::STATUS]);

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    protected function authorizeIntent(array $input)
    {
        $attributes = [
            Entity::TYPE                => Base\Type::PAY,
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
        ];

        $payment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $request = $this->getIntentRequest($input);

        if ($this->shouldSignIntentRequest() === true)
        {
            $secure = $this->getSecureInstance();

            $secure->setRequest($request);

            $data = [
                'intent_url'    => $secure->getIntentUrl(),
                'qr_code_url'   => $secure->getQrcodeUrl(),
            ];
        }
        else
        {
            $data = [
                'intent_url'    => $this->generateIntentString($request),
            ];
        }

        return ['data' => $data];
    }

    protected function getIntentRequest($input)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $input['terminal']->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA,
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => $input['payment']['id'],
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => $input['payment']['currency'],
            Base\IntentParams::MCC           => '5411',
        ];

        if (isset($input['upi']['reference_url']) === true)
        {
            $content[Base\IntentParams::URL] = $input['upi']['reference_url'];
        }

        return $content;
    }

    /**
     * We need to validate that the user's VPA is valid before proceeding with the payment
     * @param array $input
     */
    public function validateVpa(array $input)
    {
        parent::action($input, Action::VALIDATE_VPA);

        $request = $this->getValidateVpaRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::VALIDATE_VPA);

        $this->checkResponseStatus($response[ResponseFields::VPA_STATUS], Status::VPA_AVAILABLE);

        return $this->returnValidateVpaResponse($response);
    }

    private function checkResponseStatus(string $status, string $successStatus = Status::SUCCESS)
    {
        if ($status !== $successStatus)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));

            if ($this->action === Action::AUTHENTICATE)
            {
                $ex->markSafeRetryTrue();
            }

            throw $ex;
        }
    }

    /**
     * @param string $status
     * @param string $successStatus
     * @param array $response
     * @throws Exception\GatewayErrorException
     */
    private function checkRefundResponseStatus(string $status, string $successStatus = Status::REFUND_SUCCESS, array $response = [])
    {
        if ($status !== $successStatus)
        {
            $responseKey = ($this->action === Action::VERIFY) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE : Payment\Gateway::GATEWAY_RESPONSE;

            $errorCode = ErrorCodes\ErrorCodes::getErrorCode($response);

            $errorMessage = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[ResponseFields::RESPCODE],
                $errorMessage,
                [
                    $responseKey                    => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS   => $this->getGatewayData($response)
                ]);
        }
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
    protected function getGatewayEntityAttributes(
        array $input,
        string $action = Action::AUTHORIZE,
        string $type = Base\Type::COLLECT)
    {
        $attrs = [
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::VPA                 => $input['payment']['vpa'],
            Entity::ACTION              => $action,
            Entity::TYPE                => $type,
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
     * Takes in S2S request input array
     * and returns the parsed response as an array
     *
     * @param  array $input Request Input arrau
     * @param bool   $isBharatQr
     *
     * @return array
     */
    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        $encryptedResponse = $input[ResponseFields::CALLBACK_RESPONSE_KEY];

        $response = $this->parseGatewayResponse($encryptedResponse, Action::CALLBACK);

        $response[ResponseFields::CALLBACK_RESPONSE_PGMID] = $input[ResponseFields::CALLBACK_RESPONSE_PGMID];

        $bankDetails = $this->parseBankAccountDetails($response[ResponseFields::BANK_REFERENCE]);

        $response = array_merge($response, $bankDetails);

        if ($isBharatQr === true)
        {
            $this->checkCallbackResponseStatus($response);

            $response = $this->getQrData($response);
        }

        return $response;
    }

    public function getTerminalDetailsFromCallbackIfApplicable($input)
    {
        return [
            Terminal\Entity::GATEWAY_MERCHANT_ID => $input[ResponseFields::CALLBACK_RESPONSE_PGMID]
        ];
    }

    protected function getQrData(array $input)
    {
        $amount = $this->getIntegerFormattedAmount($input[ResponseFields::AMOUNT]);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $amount,
            BharatQr\GatewayResponseParams::VPA                   => $input[ResponseFields::PAYER_VA],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::UPI,
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[ResponseFields::CALLBACK_RESPONSE_PGMID],
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => substr($input[ResponseFields::PAYMENT_ID], 3),
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[ResponseFields::UPI_TXN_ID],
        ];

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    /**
     * @param $responseBody
     * @param string $type
     * @return array
     * @see https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
     */
    protected function parseGatewayResponse($responseBody, $type = Action::COLLECT)
    {
        $response = null;

        try
        {
            $response = $this->decrypt($responseBody);

            $type = strtoupper($type);

            $fields = constant(__NAMESPACE__ . "\ResponseFields::$type");

            $values = explode('|', $response);

            $result = [];

            foreach ($fields as $index => $key)
            {
                $result[$key] = $values[$index];
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
                'body'              => $responseBody,
                'decrypted'         => $response,
                'gateway'           => $this->gateway,
                'type'              => $type,
                'error'             => $e->getMessage()
            ]);

            $responseKey = ($this->action === Action::VERIFY) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE : Payment\Gateway::GATEWAY_RESPONSE;

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                $e->getMessage(),
                [
                    $responseKey  => $responseBody,
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'decrypted'         => $response,
            'parsed'            => $result,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);

        return $result;
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

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        if ($gatewayPayment->getType() !== Base\Type::PAY)
        {
            assertTrue($content[ResponseFields::UPI_TXN_ID] === $gatewayPayment->getGatewayPaymentId());
        }

        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'parsed'            => $content,
            'type'              => $gatewayPayment->getType()
        ]);

        assertTrue($input['payment']['id'] === $content[ResponseFields::PAYMENT_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        $this->checkCallbackResponseStatus($content);

        // Gateways must return array in callback
        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    /**
     * @param $response
     * @param string $successStatus
     * @throws Exception\GatewayErrorException
     */
    private function checkCallbackResponseStatus($response, string $successStatus = Status::SUCCESS)
    {
        if ($response[ResponseFields::STATUS] !== $successStatus)
        {
            $errorCode = ErrorCodes\ErrorCodes::getErrorCode($response, Action::CALLBACK);

            $errorMessage = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[ResponseFields::STATUS],
                $errorMessage);
        }
    }

    protected function parseBankAccountDetails($bankReference)
    {
        $fields = constant(__NAMESPACE__ . '\ResponseFields::BANK_DETAILS');

        $values = explode(ResponseFields::BANK_REFERENCE_SEPARATOR, $bankReference);

        $bankReferenceArray = [];

        $index = 0;

        if (empty($values) === false)
        {
            foreach ($fields as $key)
            {
                if ($values[$index] !== ResponseFields::NO_BANK_DETAIL)
                {
                    $bankReferenceArray[$key] = $values[$index];
                }

                $index++;
            }

        }

        return $bankReferenceArray;
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attributes = $this->getMappedAttributes($response);

        // To mark that we have received a callback for this payment/refund
        $attributes[Entity::RECEIVED] = 1;

        $payment->fill($attributes);

        $payment->generatePspData($attributes);

        $this->repo->saveOrFail($payment);
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
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
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
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        return $this->config['gateway_encryption_key'];
    }

    /**
     * Encrypts data
     *
     * @param $plaintext
     *
     * @return string
     */
    public function encrypt($plaintext)
    {
        return $this->getCipherInstance()
                    ->encrypt($plaintext);
    }

    /**
     * Returns a Crypto instance
     * @return Crypto class instance
     * @return Crypto
     */
    protected function getCipherInstance()
    {
        $key = $this->getEncryptionKey();

        if (empty($this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET]) === false)
        {
            $key = $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET];
        }

        return new Crypto($key);
    }

    /**
     * Decrypts responses from the Mindgate API
     *
     * @param string $cipherText
     *
     * @return string
     */
    public function decrypt(string $cipherText)
    {
        return $this->getCipherInstance()
                    ->decrypt($cipherText);
    }

    protected function getAuthorizeRequestArray($input)
    {
        $payment = $input['payment'];

        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $payment['id'],
            $payment['vpa'],
            $this->formatAmount($payment['amount']),
            $this->getPaymentRemark($input),
            $input['upi']['expiry_time'],
            $this->getMerchantCategoryCode($input),
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
        ];

        if ($input['merchant']->isTPVRequired() === true)
        {
            // MEBR is the request type for TPV
            $data[12] = 'MEBR';
            $data[13] = $input['order']['account_number'];
        }

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $payment['id'],
            ]);

        return $request;
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string.
     *                  Default value is 6012, as per HDFC
     *                  (Check pgtech group)
     */
    protected function getMerchantCategoryCode(array $input)
    {
        return $input['merchant']['category'] ?: '6012';
    }

    /**
     * Returns a refund description, capped to 50 chars
     * @param  array  $input
     * @return string
     */
    protected function getRefundRemark(array $input): string
    {
        $description = $input['merchant']->getFilteredDba();

        // Using ?: works with empty strings as well
        // (because '' == false) === true
        $description = $description ?: 'Razorpay';

        return 'Refund for ' . substr($description, 0, 36);
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data)
    {
        $extraFields = self::FIELD_LENGTH[$this->action] - count($data);

        // We have space for 10 extra fields that we don't use
        $suffixArray = array_fill(0, $extraFields, 'NA');

        $data = array_merge($data, $suffixArray);

        // Drop any `|` in any of the field values
        $data = array_map(function($e)
        {
            return str_replace('|', '', $e);
        }, $data);

        $data = implode('|', $data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'data'              => $data,
                'gateway'           => $this->gateway,
                'action'            => $this->action
            ]);

        $msg = $this->encrypt($data);

        $json = [
            'requestMsg'    => $msg,
            'pgMerchantId'  => $this->getMerchantId(),
        ];

        return json_encode($json);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::REFUND);

        $refund = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::REFUND);

        $response[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($refund, $response);

        $this->checkRefundResponseStatus($response[ResponseFields::STATUS], Status::REFUND_SUCCESS, $response);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
        ];
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                ResponseFields::UPI_TXN_ID          => $response[ResponseFields::UPI_TXN_ID] ?? null,
                ResponseFields::NPCI_UPI_TXN_ID     => $response[ResponseFields::NPCI_UPI_TXN_ID] ?? null,
                ResponseFields::PAYER_VA            => $response[ResponseFields::PAYER_VA] ?? null,
                ResponseFields::TXN_AUTH_DATE       => $response[ResponseFields::TXN_AUTH_DATE] ?? null,
                ResponseFields::RESPCODE            => $response[ResponseFields::RESPCODE] ?? null,
            ];
        }

        return [];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        // update input using the merchant_reference in the gateway payment
        $input['gateway']['merchant_reference'] = $verify->payment['merchant_reference'];

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $bankDetails = $this->parseBankAccountDetails($content[ResponseFields::BANK_REFERENCE]);

        $content = array_merge($content, $bankDetails);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function sendRefundVerifyRequest(array $input)
    {
        $request = $this->getRefundVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'raw_content' => $response->body,
                'content'     => $content,
                'gateway'     => 'upi_mindgate',
                'refund_id'   => $input['refund']['id'],
            ]);

        return $content;
    }

    protected function getValidateVpaRequestArray(array $input): array
    {
        $data = [
            $this->getMerchantId(),
            random_alpha_string(10),
            $input['vpa'],
            'T'
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'action'            => Action::VALIDATE_VPA,
            ]);

        return $request;
    }

    protected function getRefundRequestArray(array $input): array
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $refund = $input['refund'];
        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $this->getRefundId($refund),
            $gatewayPayment[Entity::MERCHANT_REFERENCE] ?: $gatewayPayment[Entity::PAYMENT_ID],
            $gatewayPayment->getGatewayPaymentId(),
            $gatewayPayment->getNpciReferenceId(),
            $this->getRefundRemark($input),
            $this->formatAmount($input['refund']['amount']),
            $input['refund']['currency'],
            // Transaction Type
            // Refunds are P2P!
            self::P2P,
            // Type of Payment (Pay or Collect)
            // Refunds are considered "Pay" transactions
            self::PAY,
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'refund_id'         => $input['refund']['id'],
            ]);

        return $request;
    }

    /**
     * This is done in order to fix duplicate merchant transaction id issue in case refund is retried multiple times.
     *
     * UPI gateways do not process refund which has been failed, they process new refund everytime. And hence,
     * we send the refund id appended with attempts to generate new refund id.
     *
     * @param array $refund
     * @return string
     */
    protected function getRefundId(array $refund)
    {
        return $refund['id'] . ($refund['attempts'] ?: '');
    }


    protected function getPaymentVerifyRequestArray($input)
    {
        $reference = $input['gateway']['merchant_reference'] ?: $input['payment']['id'];

        $data = [
            $this->getMerchantId(),
            $reference,
            '',
            // This is the Reference ID field
            // which is supposed to be empty for now
            // Non-empty values give error
            '',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'text/plain'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
            ]);

        return $request;
    }

    protected function getRefundVerifyRequestArray($input)
    {
        //
        // Appending (attempt count - 1)  to refund id for verifying previous refund if that was successful.
        // For scrooge refunds, attempts are sent from scrooge which signifies the attempts which have been done on this.
        // As attempts in scrooge starts with 0, For eg. if attempts = 5,
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
            $this->getMerchantId(),
            $input['refund']['id'] . $attempts,
            //As confirmed by hdfc team gateway payment id is not needed
            '',
            // This is the Reference ID field
            // which is supposed to be empty for now
            // Non-empty values give error
            '',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'text/plain'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data
            ]);

        return $request;
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

            $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $this->updateGatewayPaymentEntity($verify->payment, $content);
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new GatewayBase\ScroogeResponse();

        if ($this->isUnprocessedRefund($input) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::REFUND_MANUALLY_CONFIRMED_UNPROCESSED)
                                   ->toArray();
        }

        if ($this->isProcessedRefund($input) === true)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        $content = $this->sendRefundVerifyRequest($input);

        $errorCode = UpiErrorCodes::getApiErrorCode($content[ResponseFields::RESPCODE]);

        $scroogeResponse->setStatusCode($errorCode)
                        ->setGatewayVerifyResponse($content)
                        ->setGatewayKeys($this->getGatewayData($content));

        if ($content[ResponseFields::STATUS] === Status::REFUND_SUCCESS)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        if (($content[ResponseFields::RESPCODE] === '00') and
            ($content[ResponseFields::STATUS] !== Status::REFUND_SUCCESS))
        {
            $this->checkRefundResponseStatus($content[ResponseFields::STATUS], Status::REFUND_SUCCESS, $content);
        }

        if (($content[ResponseFields::STATUS] === Status::FAILURE) or
            ($content[ResponseFields::STATUS] === Status::REFUND_FAILED))
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED)
                                   ->toArray();
        }

        $this->checkRefundResponseStatus($content[ResponseFields::STATUS], Status::REFUND_SUCCESS, $content);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = ($content[ResponseFields::STATUS] === Status::SUCCESS);
    }

    /**
     * Returns Payment Id
     *
     * @param array $response
     *
     * @return string Payment Id
     */
    public function getPaymentIdFromServerCallback(array $response)
    {
        return $response[ResponseFields::PAYMENT_ID];
    }

    protected function isDuplicateUnexpectedPayment($callbackData)
    {
        $merchantReference = $callbackData[ResponseFields::PAYMENT_ID];

        $gatewayPayment = $this->repo->fetchByMerchantReference($merchantReference);

        if ($gatewayPayment !== null)
        {
            throw new Exception\LogicException(
                'Duplicate Gateway payment found',
                null,
                [
                    'callbackData' => $callbackData
                ]
            );
        }
    }

    protected function isValidUnexpectedPayment($callbackData)
    {
        /*
            Verifies if the payload specified in the server callback is valid.
        */

        $paymentId = $callbackData[ResponseFields::PAYMENT_ID];

        $input = [
            'gateway' => [
                'merchant_reference' => $paymentId,
            ]
        ];

        $this->action = Action::VERIFY;

        $request = $this->getPaymentVerifyRequestArray($input);

        $this->action = Action::VALIDATE_PUSH;

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $this->checkResponseStatus($content[ResponseFields::STATUS]);
    }

    public function getParsedDataFromUnexptectedCallback($callbackData)
    {
        $payment = [
            'method'   => 'upi',
            'amount'   => (int) ($callbackData[ResponseFields::AMOUNT] * 100),
            'currency' => 'INR',
            'vpa'      => $callbackData[ResponseFields::PAYER_VA],
            'contact'  => '+919999999999',
            'email'    => 'void@razorpay.com',
        ];

        $terminal = [
            'gateway_merchant_id' => $callbackData[ResponseFields::CALLBACK_RESPONSE_PGMID]
        ];

        return [
            'payment'  => $payment,
            'terminal' => $terminal
        ];
    }

    public function validatePush($input)
    {
        parent::action($input, Action::VALIDATE_PUSH);

        $this->isDuplicateUnexpectedPayment($input);

        $this->isValidUnexpectedPayment($input);
    }

    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;

        $gatewayInput = [
            'payment' => [
                'id'     => $paymentId,
                'vpa'    => $callbackData[ResponseFields::PAYER_VA],
                'amount' => (int) ($callbackData[ResponseFields::AMOUNT] * 100),
            ],
            'upi'     => [
                'expiry_time' => 1, // dummy value
            ]
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = $this->getGatewayEntityAttributes($gatewayInput, Action::AUTHORIZE, Base\Type::PAY);

        $callbackData[Entity::RECEIVED] = 1;

        // Update merchant reference and payment_id
        $merchantReference = $callbackData[ResponseFields::PAYMENT_ID];

        $callbackData[ResponseFields::PAYMENT_ID] = $paymentId;

        $callbackData[Entity::MERCHANT_REFERENCE] = $merchantReference;

        $attributes = array_merge($attributes, $callbackData);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];
    }

    /**
     * This function authorize the payment forcefully when verify api is not supported
     * or not giving correct response.
     *
     * @param $input
     * @return bool
     */
    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'],
                                                                      Action::AUTHORIZE);

        // If it's already authorized on gateway side, there's nothing to do here. We just return back.
        if ((($gatewayPayment[Entity::STATUS_CODE] === Status::SUCCESS) or
            ($gatewayPayment[Entity::STATUS_CODE] === '00')) and
            ($gatewayPayment[Entity::RECEIVED] === true))
        {
            return true;
        }

        $attributes = [
            Base\Entity::STATUS_CODE        => Status::SUCCESS,
            Base\Entity::NPCI_REFERENCE_ID  => $input['gateway']['reference_number'],
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }


    protected function getBharatQrGatewayAttributes($input)
    {
        $attrs = [
            Entity::TYPE                    => Base\Type::PAY,
            Entity::RECEIVED                => true,
            Entity::MERCHANT_REFERENCE      => $input['payment']['receiver_id'],
            Entity::VPA                     => $input[ResponseFields::PAYER_VA],
            ResponseFields::UPI_TXN_ID      => $input[ResponseFields::UPI_TXN_ID],
            ResponseFields::NPCI_UPI_TXN_ID => $input[ResponseFields::NPCI_UPI_TXN_ID],
            ResponseFields::ACCOUNT_NUMBER  => $input[ResponseFields::ACCOUNT_NUMBER],
            ResponseFields::IFSC_CODE       => $input[ResponseFields::IFSC_CODE],
            ResponseFields::RESPCODE        => $input[ResponseFields::RESPCODE],
        ];

        return $attrs;
    }

    protected function returnValidateVpaResponse($response)
    {
        if (isset($response[ResponseFields::PAYER_NAME]) === true)
        {
            return $response[ResponseFields::PAYER_NAME];
        }
    }

    protected function initiateIntentTpv($input)
    {
        $this->action = Action::INTENT_TPV;

        $data = [
            $this->getMerchantId(),
            $input['payment']['id'],
            $this->getMerchantCategoryCode($input),
            self::P2M,
            self::PAY,
            $this->getPaymentRemark($input),
            '',
            '',
            $this->formatAmount($input['payment']['amount']),
            '',
            '',
            '',
            '',
            '',
            'MEBR',
            $input['order']['account_number'],
            'NA',
            'NA',
            'NA',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::INTENT_TPV);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        $status = $response[ResponseFields::STATUS];

        if ($status !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));

            $ex->markSafeRetryTrue();

            throw $ex;
        }
    }
}
