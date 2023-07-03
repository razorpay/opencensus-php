<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;

class MerchantDetail extends Base implements ParityInterface
{
    protected $merchantDetail;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->merchantDetail = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDetail();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkParity()
    {
        $this->checkParityForGetById($this->merchantId);
    }

    /**
     * @throws \Exception
     */
    function checkParityForGetById(string $merchantId): void
    {
        $merchantDetailForFindOrFailFromAPIDb = $this->repo->merchant_detail->findOrFailDatabase($merchantId);
        $merchantDetailForFindOrFailFromASV = $this->repo->merchant_detail->findOrFail($merchantId);

        $merchantDetailForFindOrFailPublicFromAPIDb = $this->repo->merchant_detail->findOrFailPublicDatabase($merchantId);
        $merchantDetailForFindOrFailPublicFromASV = $this->repo->merchant_detail->findOrFailPublic($merchantId);


        $findOrFailMerchantDetailFromAPIDbRawAttributes = $merchantDetailForFindOrFailFromAPIDb->getAttributes();
        $findOrFailMerchantDetailFromAPIDbArray = $merchantDetailForFindOrFailFromAPIDb->toArray();

        $findOrFailPublicMerchantDetailFromAPIDbRawAttributes = $merchantDetailForFindOrFailPublicFromAPIDb->getAttributes();
        $findOrFailPublicMerchantDetailFromAPIDbArray = $merchantDetailForFindOrFailPublicFromAPIDb->toArray();

        $findOrFailMerchantDetailFromASVRawAttributes = $merchantDetailForFindOrFailFromASV->getAttributes();
        $findOrFailMerchantDetailFromASVArray = $merchantDetailForFindOrFailFromASV->toArray();

        $findOrFailPublicMerchantDetailFromASVRawAttributes = $merchantDetailForFindOrFailPublicFromASV->getAttributes();
        $findOrFailPublicMerchantDetailFromASVArray = $merchantDetailForFindOrFailPublicFromASV->toArray();


        $diffRaw_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($findOrFailMerchantDetailFromAPIDbRawAttributes, $findOrFailPublicMerchantDetailFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($findOrFailMerchantDetailFromAPIDbArray, $findOrFailPublicMerchantDetailFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetById_FindORFailDB_FindOrFailPublicDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicDb,
            Constant::API_ENTITY_ARRAY . '_findOrFail' => $findOrFailMerchantDetailFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES . '_findOrFail' => $findOrFailMerchantDetailFromAPIDbRawAttributes,
            Constant::API_ENTITY_ARRAY . '_findOrFailPublic' => $findOrFailPublicMerchantDetailFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES . '_findOrFailPublic' => $findOrFailPublicMerchantDetailFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicDb, $diffArray_findOrFailDB_FindOrFailPublicDb, $logDetailMatched, $logDetailsUnMatched);


        $diffRaw_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($findOrFailMerchantDetailFromAPIDbRawAttributes, $findOrFailMerchantDetailFromASVRawAttributes);
        $diffArray_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($findOrFailMerchantDetailFromAPIDbArray, $findOrFailMerchantDetailFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailAsvDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantDetailFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantDetailFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailMerchantDetailFromASVArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantDetailFromASVRawAttributes
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailAsvDb, $diffArray_findOrFailDB_FindOrFailAsvDb, $logDetailMatched, $logDetailsUnMatched);


        $diffRaw_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($findOrFailMerchantDetailFromAPIDbRawAttributes, $findOrFailPublicMerchantDetailFromASVRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($findOrFailMerchantDetailFromAPIDbArray, $findOrFailPublicMerchantDetailFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantDetailFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantDetailFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailPublicMerchantDetailFromASVArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailPublicMerchantDetailFromASVRawAttributes
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicAsvDb, $diffArray_findOrFailDB_FindOrFailPublicAsvDb, $logDetailMatched, $logDetailsUnMatched);
    }
}
