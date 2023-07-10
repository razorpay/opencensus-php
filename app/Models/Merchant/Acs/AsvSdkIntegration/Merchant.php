<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\Merchant as MerchantProtoMapper;

class Merchant extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantEntity
    {
        /**
         * @var MerchantV1\MerchantResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getMerchant()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $merchant = $response->getMerchant();

        return (new MerchantProtoMapper($merchant))->ToEntity();
    }
}
