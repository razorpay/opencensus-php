<?php

namespace RZP\Gateway\Upi\Sbi;

use Mockery\Exception;
use phpseclib\Crypt\AES;
use Razorpay\Api\Request;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ACQUIRER = 'sbi';

    protected $gateway = 'upi_mindgate_sbi';

    const BANK = 'sbi';

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    protected $map = [
        // Mapping entity variables to entity variables
        Base\Entity::GATEWAY_MERCHANT_ID    => Base\Entity::GATEWAY_MERCHANT_ID,
        Base\Entity::VPA                    => Base\Entity::VPA,
        Base\Entity::ACTION                 => Base\Entity::ACTION,

        // Mapping response fields to entity variables
        ResponseFields::NPCI_TRANSACTION_ID => Base\Entity::NPCI_REFERENCE_ID,
        ResponseFields::STATUS              => Base\Entity::STATUS_CODE,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $request = $this->getAuthorizeRequestData($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->updateGatewayEntityResponse($gatewayPayment, $response);

        $this->checkResponseStatus($response);

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    protected function checkResponseStatus(array $response)
    {
        $status = $response[ResponseFields::API_RESPONSE][ResponseFields::STATUS];

        if (Status::isStatusSuccess($status) === false)
        {
            // TODO: Complete this
            throw new GatewayErrorException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $content = [
            RequestFields::ADDITIONAL_INFO  => [
                RequestFields::ADDITIONAL_INFO9  => Constants::NOT_APPLICABLE,
                RequestFields::ADDITIONAL_INFO10 => Constants::NOT_APPLICABLE,
            ],
            RequestFields::AMOUNT           => $input['payment']['amount'] / 100,
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
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'content'           => $content
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
        return json_decode($body, true);
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
        $attr = $this->getMappedAttributes($response[ResponseFields::API_RESPONSE]);

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $upiEntity->fill($attr);

        $upiEntity->saveOrFail();
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}