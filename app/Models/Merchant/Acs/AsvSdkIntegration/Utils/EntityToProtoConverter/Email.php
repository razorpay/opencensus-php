<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Email\Entity as MerchantEmail;

class Email implements EntityToProtoConvertorInterface
{
    protected MerchantEmail $entity;

    function __construct(MerchantEmail $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $email = new MerchantV1\Email();

        $rawAttributes = $this->entity->getAttributes();

        $email->setId(Helper::notNullCheck($rawAttributes, MerchantEmail::ID));
        $email->setMerchantId(Helper::notNullCheck($rawAttributes, MerchantEmail::MERCHANT_ID));
        $email->setEmail(Helper::converToStringValue($rawAttributes,MerchantEmail::EMAIL));
        $email->setType(Helper::notNullCheck($rawAttributes, MerchantEmail::TYPE));
        $email->setVerified(Helper::notNullCheck($rawAttributes, MerchantEmail::VERIFIED));
        $email->setPhone(Helper::converToStringValue($rawAttributes,MerchantEmail::PHONE));
        $email->setPolicy(Helper::converToStringValue($rawAttributes, MerchantEmail::POLICY));
        $email->setUrl(Helper::converToStringValue($rawAttributes, MerchantEmail::URL));
        $saveRequest->setMerchantEmails([$email]);
        return $saveRequest;
    }
}
