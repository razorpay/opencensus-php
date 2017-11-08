<?php

namespace RZP\Gateway\Upi\Sbi;

use App;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\AES;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Exception\GatewayErrorException;
use RZP\Constants\Entity as ConstantsEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = 'sbi';

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

    public function __construct()
    {
        parent::__construct();

        //
        // When this class is instantiated during the async callback flow via test cases,
        // $this->mode is not set, and is therefore null. But we need mode to be set
        // to be able to get the test secret for decryption. Setting $this->mode below.
        //

        // TODO: This will not work, as the above case happens in direct auth, and we won't get mode

        // Setting Test for now so that UAT can be tested
        $mode = $this->app['rzp.mode'] ?? Mode::TEST;

        $this->setMode($mode);
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $request = $this->getAuthorizeRequest($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, TraceCode::GATEWAY_PAYMENT_RESPONSE);

        $this->assertPaymentIdAndAmount($input, $response);

        $this->updateGatewayEntityResponse($gatewayPayment, $response[ResponseFields::API_RESPONSE]);

        $this->checkResponseStatus($response[ResponseFields::API_RESPONSE][ResponseFields::STATUS]);

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

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
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'][ResponseFields::API_RESPONSE];

        $this->assertPaymentIdAndAmount($input, $input['gateway']);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                                                                      Action::AUTHORIZE);

        assertTrue($content[ResponseFields::UPI_TRANS_REFERENCE_NO] === $gatewayPayment->getNpciReferenceId());

        $this->checkResponseStatus($content[ResponseFields::STATUS]);

        // Authorization was successful
        $this->updateGatewayEntityResponse($gatewayPayment, $content);

        return [];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function assertPaymentIdAndAmount(array $input, array $response)
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

        $this->setVerifyStatus($verify);
    }

    /**
     * @param Verify $verify
     * @return array
     */
    protected function getPaymentVerifyRequest(Verify $verify): array
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

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input);

        $content = $verify->verifyResponseContent[ResponseFields::API_RESPONSE];

        $actualAmount = number_format($content[ResponseFields::AMOUNT] / 100, 2, '.', '');

        $verify->amountMismatch = ($paymentAmount !== $actualAmount);
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->setApiSuccess($verify);

        $this->setGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    protected function setApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input[ConstantsEntity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($input[ConstantsEntity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function setGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = $content[ResponseFields::API_RESPONSE][ResponseFields::STATUS];

        $verify->gatewaySuccess = (Status::isStatusSuccess($status) === true);
    }

    // TODO: Validate VPA before sending collect request and don't create payment entity if VPA is invalid

    /**
     * @param string $status
     * @throws GatewayErrorException
     */
    protected function checkResponseStatus(string $status)
    {
        if (Status::isStatusSuccess($status) === false)
        {
            $errorCode = Status::getErrorCode($status);

            $errorMessage = Status::getMessage($status);

            throw new GatewayErrorException($errorCode, $status, $errorMessage);
        }
    }

    protected function getRequestTraceCode(): string
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST;
                break;

            case Action::CALLBACK:
                $traceCode = TraceCode::GATEWAY_PAYMENT_CALLBACK;
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

    protected function getAuthorizeRequest(array $input): array
    {
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
        $json = json_encode($content);

        // TODO: Ensure this is correct
        return $this->getAesCrypto()->encryptString($json);
    }

    public function decrypt(string $encryptedResponse): array
    {
        $decryptedString = $this->getAesCrypto()->decryptString($encryptedResponse);

        return json_decode($decryptedString, true);
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
    protected function parseGatewayResponse(string $body, $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE): array
    {
        $this->trace->info($traceCode,
            [
                'encrypted'  => true,
                'response'   => $body,
                'gateway'    => $this->gateway,
                'payment_id' => $this->input['payment']['id']
            ]);

        $encryptedResponse = json_decode($body, true)[ResponseFields::RESPONSE];

        $response = $this->decrypt($encryptedResponse);

        $this->trace->info($traceCode,
            [
                'encrypted'  => false,
                'response'   => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $this->input['payment']['id']
            ]);

        return $response;
    }

    /**
     * @override
     *
     * This method encrypts the request content before converting the request into standard form for Sbi's API's
     *
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
    protected function getGatewayEntityAttributes(array $input): array
    {
        $attributes = [
            Base\Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Base\Entity::VPA                 => $input[ConstantsEntity::PAYMENT][Payment\Entity::VPA],
            Base\Entity::ACTION              => $this->action,
        ];

        return $attributes;
    }

    protected function updateGatewayEntityResponse(Entity $upiEntity, array $response)
    {
        $attr = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $upiEntity->fill($attr);

        $upiEntity->saveOrFail();
    }

    protected function formatAmount(array $input): string
    {
        return number_format($input[ConstantsEntity::PAYMENT][Payment\Entity::AMOUNT] / 100, '2', '.', '');
    }

    public function getMerchantId(): string
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    /**
     * @param $input
     * @return array
     */
    public function preProcessServerCallback($input): array
    {
        $response = json_decode($input[ResponseFields::MESSAGE], true)[ResponseFields::RESPONSE];

        $json = $this->getAesCrypto()->decryptString($response);

        return json_decode($json, true);
    }

    /**
     * @param array $response
     * @return mixed
     */
    public function getPaymentIdFromServerCallback(array $response): string
    {
        return $response[ResponseFields::API_RESPONSE][ResponseFields::PSP_REFERENCE_NO];
    }
}
