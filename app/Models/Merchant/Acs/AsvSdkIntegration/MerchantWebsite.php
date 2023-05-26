<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;


use phpDocumentor\Reflection\Types\Null_;
use Razorpay\Asv\RequestMetadata;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\MerchantWebsite as MerchantWebsiteProtoMapper;
use RZP\Models\Base\PublicCollection;
use Razorpay\Asv\DbSource;
use RZP\Trace\TraceCode;

class MerchantWebsite extends Base
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
         * @var MerchantV1\MerchantWebsiteResponseByMerchantId $response
         */
        list($response, $err) = $this->asvSdkClient->getWebsite()->getByMerchantId(
            $merchantId,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null){
            $this->handleError($err);
        }

        $websitesArray = [];

        $websites = $response->getWebsites();

        /**
         * @var $website MerchantV1\MerchantWebsite
         */
        foreach ($websites as $website) {
            $merchantWebsiteProtoConvertor = new MerchantWebsiteProtoMapper($website);
            $websiteEntity = $merchantWebsiteProtoConvertor->ToEntity();
            $websitesArray[] = $websiteEntity;
        }

        return (new MerchantWebsiteEntity)->newCollection($websitesArray);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantWebsiteEntity
    {
        /**
         * @var MerchantV1\MerchantWebsiteResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getWebsite()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $website = $response->getWebsite();

        return (new MerchantWebsiteProtoMapper($website))->ToEntity();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getLatestByMerchantId(string $id, RequestMetadata $requestMetadata = null): ?MerchantWebsiteEntity
    {
        try {
            /**
             * @var MerchantV1\MerchantWebsiteResponseByMerchantId $response
             */
            $merchantWebsitesForMerchantId = $this->getByMerchantId($id, $requestMetadata);
        } catch (\Exception $e) {
            if($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return null;
            }

            throw $e;
        }

        // Account Service, By default returns the ordering by created at desc. To get the latest element
        // we need to return the first element from the response.
        return $merchantWebsitesForMerchantId->first();
    }

    public function getLatestByMerchantIdCallBack(string $id, ?RequestMetadata $requestMetadata = null): \Closure
    {
       return function() use ($id, $requestMetadata) {
            return $this->getLatestByMerchantId($id, $requestMetadata);
        };
    }
    
}
