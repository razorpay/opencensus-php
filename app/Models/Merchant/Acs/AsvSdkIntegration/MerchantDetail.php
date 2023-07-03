<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\MerchantDetail as MerchantDetailProtoMapper;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;

class MerchantDetail extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantDetailEntity
    {
        /**
         * @var MerchantV1\MerchantDetailResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getMerchantDetail()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $Detail = $response->getMerchantDetail();

        return (new MerchantDetailProtoMapper($Detail))->ToEntity();
    }
}
