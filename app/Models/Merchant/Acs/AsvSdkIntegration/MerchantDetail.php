<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use Rzp\Accounts\Merchant\V1\FilterRequest;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\MerchantDetail as MerchantDetailProtoMapper;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;

class MerchantDetail extends Base
{
    const FILTER_MERCHANTS_BY_ACTIVATION_STATUS
        = 'filter_merchants_by_activation_status';

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


    /**
     * @param array  $merchantIds
     * @param string $activationStatus
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function filterMerchantsByActivationStatus(array $merchantIds, string $activationStatus): Collection|PublicCollection
    {
        $filterRequest = new FilterRequest();
        $filterRequest->setQueryIdentifier(self::FILTER_MERCHANTS_BY_ACTIVATION_STATUS);
        $filterRequest->setBindings(json_encode([$merchantIds, $activationStatus]));

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantDetailCollectionFromResponse($response);
    }

}
