<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;


use Razorpay\Asv\RequestMetadata;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\BusinessDetail as BusinessDetailWrapper;
use RZP\Models\Base\PublicCollection;

class BusinessDetail extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getByMerchantId(string $merchantId, RequestMetadata $requestMetadata = null): PublicCollection
    {
        /**
         * @var MerchantV1\MerchantBusinessDetailResponseByMerchantId $response
         */
        list($response, $err) = $this->asvSdkClient->getBusinessDetail()->getByMerchantId(
            $merchantId,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null){
            $this->handleError($err);
        }

        $businessDetailsArray = [];

        $businessDetails = $response->getBusinessDetails();

        /**
         * @var $businessDetail MerchantV1\BusinessDetail
         */
        foreach ($businessDetails as $businessDetail) {
            $businessDetailProtoConvertor = new BusinessDetailWrapper($businessDetail);
            $businessDetailEntity = $businessDetailProtoConvertor->ToEntity();
            $businessDetailsArray[] = $businessDetailEntity;
        }

        return (new BusinessDetailEntity)->newCollection($businessDetailsArray);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): BusinessDetailEntity
    {
        /**
         * @var MerchantV1\MerchantBusinessDetailResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getBusinessDetail()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null){
            $this->handleError($err);
        }

        $businessDetail = $response->getBusinessDetail();

        return (new BusinessDetailWrapper($businessDetail))->ToEntity();
    }
}
