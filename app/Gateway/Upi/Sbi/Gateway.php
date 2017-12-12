<?php

namespace RZP\Gateway\Upi\Sbi;

use App;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Exception\BaseException;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Exception\AssertionException;
use RZP\Exception\GatewayErrorException;
use RZP\Constants\Entity as ConstantsEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = Payment\Processor\Upi::SBIN;

    protected $gateway = Payment\Gateway::UPI_SBI;

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    /**
     * Used to map request / response fields to entity
     * fields before creating or updating the upi entity.
     * @var array
     */
    protected $map = [
        // Mapping entity variables to entity variables
        Base\Entity::GATEWAY_MERCHANT_ID       => Base\Entity::GATEWAY_MERCHANT_ID,
        Base\Entity::VPA                       => Base\Entity::VPA,
        Base\Entity::ACTION                    => Base\Entity::ACTION,

        // Mapping response fields to entity variables
        ResponseFields::CUSTOMER_REFERENCE_NO  => Base\Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::UPI_TRANS_REFERENCE_NO => Base\Entity::NPCI_REFERENCE_ID,
        ResponseFields::STATUS                 => Base\Entity::STATUS_CODE,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        // We validate the input VPA before initiating the collect request
        $this->validateVpa($input, $gatewayPayment);

        $request = $this->getCollectRequestData($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, TraceCode::GATEWAY_PAYMENT_RESPONSE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $response[ResponseFields::API_RESPONSE]);

        $this->checkResponseStatus($response[ResponseFields::API_RESPONSE][ResponseFields::STATUS]);

        $vpa = $input[ConstantsEntity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2] ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                Payment\Entity::VPA => $vpa
            ]
        ];
    }

    /**
     * Handles S2S callback flow
     *
     * @param array $input
     * @return array
     * @throws BaseException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'][ResponseFields::API_RESPONSE];

        $this->assertPaymentIdAndAmount($input, $input['gateway']);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                                                                      Action::AUTHORIZE);

        if ($this->assertUpiTransactionId($gatewayPayment, $content) === false)
        {
            throw new AssertionException(
                'Upi Transaction reference number does not match saved npci reference id in DB',
                [
                    Base\Entity::NPCI_REFERENCE_ID         => $gatewayPayment->getNpciReferenceId(),
                    ResponseFields::UPI_TRANS_REFERENCE_NO => $content[ResponseFields::UPI_TRANS_REFERENCE_NO],
                    Base\Entity::PAYMENT_ID                => $input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                ]);
        }

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->checkResponseStatus($content[ResponseFields::STATUS]);

        return [];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    private function validateVpa(array $input, Base\Entity $gatewayPayment)
    {
        parent::action($input, Action::VALIDATE_VPA);

        $request = $this->getValidateVpaRequest($input);

        $response = $this->sendGatewayRequest($request);

        $responseContent = $this->parseGatewayResponse($response->body, TraceCode::GATEWAY_VALIDATE_VPA_RESPONSE);

        // Update the gateway payment entity
        $this->updateGatewayPaymentEntity($gatewayPayment, $responseContent);

        $this->checkResponseStatus($responseContent[ResponseFields::STATUS]);
    }

    private function assertPaymentIdAndAmount(array $input, array $response)
    {
        $expectedAmount = $this->formatAmount($input);

        $actualAmount = $response[ResponseFields::API_RESPONSE][ResponseFields::AMOUNT];

        $actualAmount = number_format($actualAmount, 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $expectedPaymentId = $input[ConstantsEntity::PAYMENT][Payment\Entity::ID];

        $actualPaymentId = $response[ResponseFields::API_RESPONSE][ResponseFields::PSP_REFERENCE_NO];

        $this->assertPaymentId($expectedPaymentId, $actualPaymentId);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getPaymentVerifyRequest($verify);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $this->parseGatewayResponse($response->body);
    }

    protected function verifyPayment(Verify $verify)
    {
        $this->setVerifyAmountMismatch($verify);

        $content = $verify->verifyResponseContent[ResponseFields::API_RESPONSE];

        $this->updateGatewayPaymentEntity($verify->payment, $content);

        $this->setVerifyStatus($verify);
    }

    private function getValidateVpaRequest(array $input): array
    {
        $content = [
            RequestFields::REQUEST_INFO => [
                RequestFields::PG_MERCHANT_ID => $this->getMerchantId(),
                RequestFields::PSP_REFERENCE_NO => $input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
            ],
            RequestFields::PAYEE_TYPE => [
                RequestFields::VIRTUAL_ADDRESS => $input[ConstantsEntity::PAYMENT][Payment\Entity::VPA]
            ],
            RequestFields::VA_REQUEST_TYPE => Constants::VA_REQUEST_TYPE
        ];

        return $this->getStandardRequestArray($content);
    }

    /**
     * @param Verify $verify
     * @return array
     */
    private function getPaymentVerifyRequest(Verify $verify): array
    {
        $input = $verify->input;

        $requestInfo = [
            RequestFields::PG_MERCHANT_ID   => $this->getMerchantId(),
            RequestFields::PSP_REFERENCE_NO => $input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
        ];

        $request = [
            RequestFields::REQUEST_INFO          => $requestInfo,
            RequestFields::CUSTOMER_REFERENCE_NO => $verify->payment->getGatewayPaymentId(),
        ];

        return $this->getStandardRequestArray($request);
    }

    private function setVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input);

        $content = $verify->verifyResponseContent[ResponseFields::API_RESPONSE];

        if (empty($content[ResponseFields::AMOUNT]) === false)
        {
            $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }
    }

    private function setVerifyStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = $content[ResponseFields::API_RESPONSE][ResponseFields::STATUS];

        $verify->gatewaySuccess = (Status::isStatusSuccess($status, $this->action) === true);
    }

    /**
     * @param string $status
     * @throws GatewayErrorException
     */
    private function checkResponseStatus(string $status)
    {
        if (Status::isStatusSuccess($status, $this->action) === false)
        {
            $errorCode = Status::getErrorCode($status);

            $errorMessage = Status::getMessage($status);

            throw new GatewayErrorException($errorCode, $status, $errorMessage);
        }
    }

    private function getRequestTraceCode(): string
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST;
                break;

            case Action::VALIDATE_VPA:
                $traceCode = TraceCode::GATEWAY_VALIDATE_VPA_REQUEST;
                break;

            case Action::VERIFY:
                $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY;
                break;

            default:
                $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST;
                break;
        }

        return $traceCode;
    }

    private function getCollectRequestData(array $input): array
    {
        parent::authorize($input);

        $content = [
            RequestFields::ADDITIONAL_INFO  => [
                RequestFields::ADDITIONAL_INFO9  => Constants::NOT_APPLICABLE,
                RequestFields::ADDITIONAL_INFO10 => Constants::NOT_APPLICABLE,
            ],
            RequestFields::AMOUNT           => $this->formatAmount($input),
            RequestFields::EXPIRY_TIME      => Constants::EXPIRY_TIME,
            RequestFields::PAYER_TYPE       => [
                RequestFields::VIRTUAL_ADDRESS => $input[ConstantsEntity::PAYMENT][Payment\Entity::VPA],
            ],
            RequestFields::REQUEST_INFO     => [
                RequestFields::PG_MERCHANT_ID   => $this->getMerchantId(),
                RequestFields::PSP_REFERENCE_NO => $input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
            ],
            RequestFields::TRANSACTION_NOTE => Constants::TRANSACTION_NOTE . $input[ConstantsEntity::PAYMENT][Payment\Entity::VPA],
        ];

        return $this->getStandardRequestArray($content);
    }

    public function encrypt(array $content): string
    {
        $json = utf8_json_encode($content);

        return $this->getAesCrypto()->encryptString($json);
    }

    public function decrypt(string $encryptedResponse): array
    {
        $decryptedString = $this->getAesCrypto()->decryptString($encryptedResponse);

        return $this->jsonToArray($decryptedString);
    }

    /**
     * @return Crypto
     */
    public function getAesCrypto(): Crypto
    {
        return (new Crypto($this->getSecret()));
    }

    /**
     * @param string $body
     * @param string $traceCode
     * @return array
     */
    private function parseGatewayResponse(string $body, $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE): array
    {
        $this->trace->info($traceCode,
            [
                'encrypted'  => true,
                'response'   => $body,
                'gateway'    => $this->gateway,
                'payment_id' => $this->input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
            ]);

        $encryptedResponse = $this->jsonToArray($body)[ResponseFields::RESPONSE];

        $response = $this->decrypt($encryptedResponse);

        $this->trace->info($traceCode,
            [
                'encrypted'  => false,
                'response'   => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $this->input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
            ]);

        return $response;
    }

    private function assertUpiTransactionId(Base\Entity $upiEntity, array $content): bool
    {
        $upiTransactionRefNo = (string) $content[ResponseFields::UPI_TRANS_REFERENCE_NO];

        $npciReferenceId = (string) $upiEntity->getNpciReferenceId();

        return ($upiTransactionRefNo === $npciReferenceId);
    }

    /**
     * This method encrypts the request content before converting the request into standard form for Sbi's API's
     *
     * @override
     * @param array $content
     * @param string $method
     * @param null $type
     * @return array
     */
    protected function getStandardRequestArray($content = [], $method = 'post', $type = null): array
    {
        $traceCode = $this->getRequestTraceCode();

        $this->trace->info(
            $traceCode,
            [
                'encrypted'  => false,
                'gateway'    => $this->gateway,
                'payment_id' => $this->input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                'content'    => $content
            ]);

        $requestMsg = $this->encrypt($content);

        $json = [
            RequestFields::REQUEST_MESSAGE => $requestMsg,
            RequestFields::PG_MERCHANT_ID  => $this->getMerchantId(),
        ];

        $content = json_encode($json);

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers']['Content-Type'] = 'application/json';

        $this->trace->info(
            $traceCode,
            [
                'encrypted'  => true,
                'gateway'    => $this->gateway,
                'payment_id' => $this->input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                'request'    => $request
            ]);

        return $request;
    }

    /**
     * Gets the gateway entity attributes
     *
     * @param array $input
     * @return array
     */
    private function getGatewayEntityAttributes(array $input): array
    {
        $attributes = [
            Base\Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Base\Entity::VPA                 => $input[ConstantsEntity::PAYMENT][Payment\Entity::VPA],
            Base\Entity::ACTION              => $this->action,
        ];

        return $attributes;
    }

    private function formatAmount(array $input): string
    {
        $amount = $input[ConstantsEntity::PAYMENT][Payment\Entity::AMOUNT] / 100;

        return number_format($amount, '2', '.', '');
    }

    /**
     * @param $input
     * @return array
     */
    public function preProcessServerCallback($input): array
    {
        $response = $this->jsonToArray($input[ResponseFields::MESSAGE])[ResponseFields::RESPONSE];

        $json = $this->getAesCrypto()->decryptString($response);

        $callback = $this->jsonToArray($json);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'decrypted_data' => $callback,
                'payment_id'     => $callback[ResponseFields::API_RESPONSE][ResponseFields::PSP_REFERENCE_NO]
            ]);

        return $callback;
    }

    /**
     * @param array $response
     * @return mixed
     */
    public function getPaymentIdFromServerCallback(array $response): string
    {
        return $response[ResponseFields::API_RESPONSE][ResponseFields::PSP_REFERENCE_NO];
    }

    /**
     * Mode isn't set during the async callback flow, and we would need merchantId
     * based on whether the mode is live or test. However, we are setting the same
     * look up key in the vault file, but the key will be mapped to the live or test
     * merchant_id / hash_secret based on the environment. Since, this is handled by the
     * vault file logic, we are pulling out the merchant_id / hash_secret in the getters below
     */

    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->config['merchant_id'];
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->config['hash_secret'];
    }
}
