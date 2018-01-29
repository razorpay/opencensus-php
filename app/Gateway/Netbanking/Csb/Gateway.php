<?php

namespace RZP\Gateway\Netbanking\Csb;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Entity;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Constants\Mode as RZPMode;
use RZP\Exception\GatewayErrorException;

class Gateway extends Base\Gateway
{
    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::CHNPGSYN      => Base\Entity::REFERENCE1,
        RequestFields::CHNPGCODE     => Base\Entity::MERCHANT_CODE,
        RequestFields::AMOUNT        => Base\Entity::AMOUNT,

        /**
         * Fields from the authorize response
         */
        ResponseFields::TRAN_REF_NUM => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::STATUS       => Base\Entity::STATUS,
        ResponseFields::NARRATION    => Base\Entity::ERROR_MESSAGE, // TODO: Ensure this is correct
    ];

    /**
     * This array is modified while getting the authorize request.
     * It is used to create the gateway netbanking entity.
     * @see getAuthorizeRequest
     * @var array
     */
    private $gatewayAttributes = [];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity($this->gatewayAttributes);

        // Freeing memory occupied by this instance variable
        $this->gatewayAttributes = [];

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::BANK_REF_NUM]);

        // TODO: Is there a checksum here? If not we should verify.

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->checkResponseStatus($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        sd('Reached verify function');
    }

    /**
     * Exposing this method as a public API for the mock server to access
     *
     * @override
     * @param $str
     * @return string
     */
    public function getHashOfString($str)
    {
        // TODO: Verify that this is the right way to generate the checksum
        return hash(HashAlgo::CRC32, $str);
    }

    /**
     * Overriding this method so that it can be exposed as a public API for the mock server
     *
     * @override
     * @param $actual
     * @param $generated
     */
    public function compareHashes($actual, $generated)
    {
        parent::compareHashes($actual, $generated);
    }

    protected function updateGatewayPaymentEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        $attributes = $this->getMappedAttributes($attributes);

        $attributes[Base\Entity::RECEIVED] = true;

        return parent::updateGatewayPaymentEntity($gatewayPayment, $attributes, false);
    }

    private function checkResponseStatus(array $content)
    {
        if ((empty($content[ResponseFields::STATUS]) === false) and
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                $content);
        }
    }

    /**
     * This method gets the required authorize request as per API contract.
     * @see https://docs.google.com/document/d/153ypkOhWNIetN3kV153gevKz2EIBO4aGj4XjIguLB0Y/edit#
     *
     * @param array $input
     * @return array
     */
    private function getAuthorizeRequest(array $input): array
    {
        $content = [
            RequestFields::CHNPGSYN     => Constants::CHNPGSYN,
            RequestFields::CHNPGCODE    => Constants::CHNPGCODE,
            RequestFields::PAYEE_ID     => $this->getMerchantId(),
            RequestFields::BANK_REF_NUM => $input['payment']['id'],
            RequestFields::AMOUNT       => $input['payment']['amount'] / 100,
            RequestFields::RETURN_URL   => $input['callbackUrl'],
            RequestFields::MODE         => Mode::PAY,
        ];

        $this->gatewayAttributes = $content;

        $content = array_values($content);

        $content = implode('|', $content);

        $checkSum = $this->getHashOfString($content);

        $content = [
            RequestFields::AUTH_DATA => base64_encode($content . '|' . $checkSum)
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === RZPMode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getTestMerchantId()
    {
        // TODO: Ensure this is right
        return $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
    }
}
