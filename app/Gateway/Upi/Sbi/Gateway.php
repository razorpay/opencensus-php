<?php

namespace RZP\Gateway\Upi\Sbi;

use App;
use RZP\Constants\Mode;
use RZP\Gateway\Base\Action;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\AES;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Exception\GatewayErrorException;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = 'sbi';

    protected $gateway = 'upi_sbi';

    const BANK = 'sbi';

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    protected $map = [
        // Mapping entity variables to entity variables
        Base\Entity::GATEWAY_MERCHANT_ID       => Base\Entity::GATEWAY_MERCHANT_ID,
        Base\Entity::VPA                       => Base\Entity::VPA,
        Base\Entity::ACTION                    => Base\Entity::ACTION,

        // Mapping response fields to entity variables
        ResponseFields::NPCI_TRANSACTION_ID    => Base\Entity::NPCI_REFERENCE_ID,
        ResponseFields::UPI_TRANS_REFERENCE_NO => Base\Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::STATUS                 => Base\Entity::STATUS_CODE,
    ];

    public function __construct()
    {
        parent::__construct();

        $mode = $this->app['rzp.mode'];

        $this->setMode($mode);
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $request = $this->getAuthorizeRequestData($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->updateGatewayEntityResponse($gatewayPayment, $response[ResponseFields::API_RESPONSE]);

        $this->checkResponseStatus($response[ResponseFields::API_RESPONSE]);

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    // Handles S2S callback
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'][ResponseFields::API_RESPONSE];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        assertTrue($content[ResponseFields::UPI_TRANS_REFERENCE_NO] === $gatewayPayment->getGatewayPaymentId());

        $this->checkResponseStatus($content);

        // Authorization was successful
        $this->updateGatewayEntityResponse($gatewayPayment, $content);

        return [];
    }

    // TODO: Validate VPA before sending collect request and don't create payment entity if VPA is invalid

    protected function checkResponseStatus(array $response)
    {
        $status = $response[ResponseFields::STATUS];

        if (Status::isStatusSuccess($status) === false)
        {
            $errorCode = Status::getErrorCode($status);

            $errorMessage = Status::getMessage($status);

            throw new GatewayErrorException($errorCode, $status, $errorMessage);
        }
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $content = [
            RequestFields::ADDITIONAL_INFO  => [
                RequestFields::ADDITIONAL_INFO9  => Constants::NOT_APPLICABLE,
                RequestFields::ADDITIONAL_INFO10 => Constants::NOT_APPLICABLE,
            ],
            RequestFields::AMOUNT           => $this->formatAmount($input),
            RequestFields::EXPIRY_TIME      => Constants::EXPIRY_TIME,
            RequestFields::PAYER_TYPE       => [
                RequestFields::VIRTUAL_ADDRESS => $input['payment']['vpa'],
            ],
            RequestFields::REQUEST_INFO     => [
                RequestFields::PG_MERCHANT_ID   => $this->getMerchantId(),
                RequestFields::PSP_REFERENCE_NO => $input['payment']['id'],
            ],
            RequestFields::TRANSACTION_NOTE => Constants::TRANSACTION_NOTE . $input['payment']['vpa'],
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'content'    => $content
            ]);

        $requestMsg = $this->encrypt($content);

        $json = [
            RequestFields::REQUEST_MESSAGE => $requestMsg,
            RequestFields::PG_MERCHANT_ID  => $this->getMerchantId(),
        ];

        $content = json_encode($json);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    public function encrypt(array $content)
    {
        $json = json_encode($content);

        // TODO: Ensure this is correct
        return $this->getAesCrypto()->encryptString($json);
    }

    public function getAesCrypto()
    {
        return (new Crypto(AES::MODE_ECB, $this->getSecret()));
    }

    protected function parseGatewayResponse(string $body)
    {
        $this->trace->info(TraceCode::GATEWAY_RESPONSE,
            [
                'response'  => $body,
                'encrypted' => true,
                'gateway'   => $this->gateway,
            ]);

        $encryptedResponse = json_decode($body, true)[ResponseFields::RESPONSE];

        $decryptedString = $this->getAesCrypto()->decryptString($encryptedResponse);

        $response = json_decode($decryptedString, true);

        $this->trace->info(TraceCode::GATEWAY_RESPONSE,
            [
                'response'  => $response,
                'encrypted' => false,
                'gateway'   => $this->gateway,
            ]);

        return $response;
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers']['Content-Type'] = 'application/json';

        return $request;
    }

    /**
     * Gets the gateway entity attributes
     *
     * @param array $input
     * @return array
     */
    protected function getGatewayEntityAttributes(array $input)
    {
        $attributes = [
            Base\Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Base\Entity::VPA                 => $input['payment']['vpa'],
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

    protected function formatAmount(array $input)
    {
        return number_format($input['payment']['amount'] / 100, '2', '.', '');
    }

    public function getMerchantId()
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
        $response = json_decode($input['msg'], true)[ResponseFields::RESPONSE];

        $json = $this->getAesCrypto()->decryptString($response);

        return json_decode($json, true);
    }

    /**
     * @param array $response
     * @return mixed
     */
    public function getPaymentIdFromServerCallback(array $response)
    {
        return $response[ResponseFields::API_RESPONSE][ResponseFields::PSP_REFERENCE_NO];
    }
}