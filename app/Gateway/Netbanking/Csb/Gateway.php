<?php

namespace RZP\Gateway\Netbanking\Csb;

use RZP\Constants\HashAlgo;
use RZP\Models\Terminal;
use RZP\Gateway\Netbanking\Base;
use RZP\Constants\Mode as RZPMode;

class Gateway extends Base\Gateway
{
    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::CHNPGSYN     => Base\Entity::REFERENCE1,
        RequestFields::CHNPGCODE    => Base\Entity::MERCHANT_CODE,
        RequestFields::AMOUNT       => Base\Entity::AMOUNT,
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

        sd('Reached callback function');
    }

    public function verify(array $input)
    {
        parent::verify($input);

        sd('Reached verify function');
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

        $content .= '|' . $this->getHashOfString($content);

        $content = [
            RequestFields::AUTH_DATA => base64_encode($content)
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getHashOfString($str)
    {
        // TODO: Verify that this is the right way to generate the checksum
        hash(HashAlgo::SHA256, $str);
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
