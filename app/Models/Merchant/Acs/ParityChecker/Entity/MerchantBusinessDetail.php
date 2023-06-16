<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Modules\Acs\Comparator;

class MerchantBusinessDetail extends Base implements ParityInterface
{
    protected $businessDetail;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->businessDetail = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\BusinessDetail();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkParity()
    {
        if (in_array(Constant::GET_BY_MERCHANT_ID, $this->parityCheckMethods) === true) {
            $this->checkParityForGetLatestBusinessDetailByMerchantId($this->merchantId);
        }

        if (in_array(Constant::GET_BY_ID, $this->parityCheckMethods) === true) {
            $this->checkParityGetById($this->merchantId);
        }
        //TODO: Add parity checker for GetById
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityGetById(string $merchantId): void
    {
        $merchantBusinessDetailFromAPIDb = $this->repo->merchant_business_detail->getAllBusinessDetailsFromReplica($merchantId);

        $FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes = [];
        $FindOrFailMerchantBusinessDetailArrayFromAPIDbArray = [];

        $FindOrFailPublicMerchantBusinessDetailArrayFromAPIDbRawAttributes = [];
        $FindOrFailPublicMerchantBusinessDetailArrayFromAPIDbArray = [];

        $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes = [];
        $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbArray = [];

        $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes = [];
        $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbArray = [];

        /**
         * @var BusinessDetailEntity $merchantBusinessDetail
         */
        foreach ($merchantBusinessDetailFromAPIDb as $merchantBusinessDetail) {

            $id = $merchantBusinessDetail->id;

            $findOrFailDb = $this->repo->merchant_business_detail->findOrFailDatabase($id);
            $FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes[$id][] = $findOrFailDb->getAttributes();
            $FindOrFailMerchantBusinessDetailArrayFromAPIDbArray[$id][] = $findOrFailDb->toArray();

            $findOrFailPublicDb = $this->repo->merchant_business_detail->findOrFailPublicDatabase($id);
            $FindOrFailPublicMerchantBusinessDetailArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicDb->getAttributes();
            $FindOrFailPublicMerchantBusinessDetailArrayFromAPIDbArray[$id][] = $findOrFailPublicDb->toArray();

            $findOrFailAsvDb = $this->repo->merchant_business_detail->findOrFail($id);
            $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes[$id][] = $findOrFailAsvDb->getAttributes();
            $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbArray[$id][] = $findOrFailAsvDb->toArray();

            $findOrFailPublicAsvDb = $this->repo->merchant_business_detail->findOrFailPublic($id);
            $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicAsvDb->getAttributes();
            $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbArray[$id][] = $findOrFailPublicAsvDb->toArray();
        }


        $diffRaw_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes, $FindOrFailPublicMerchantBusinessDetailArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($FindOrFailMerchantBusinessDetailArrayFromAPIDbArray, $FindOrFailPublicMerchantBusinessDetailArrayFromAPIDbArray);
        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicDb,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicDb, $diffArray_findOrFailDB_FindOrFailPublicDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes, $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantBusinessDetailArrayFromAPIDbArray, $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailAsvDb,
            Constant::API_ENTITY_ARRAY => $FindOrFailMerchantBusinessDetailArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $FindOrFailAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailAsvDb, $diffArray_findOrFailDB_FindOrFailAsvDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes, $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantBusinessDetailArrayFromAPIDbArray, $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::API_ENTITY_ARRAY => $FindOrFailMerchantBusinessDetailArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $FindOrFailMerchantBusinessDetailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $FindOrFailPublicAsvMerchantBusinessDetailArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicAsvDb, $diffArray_findOrFailDB_FindOrFailPublicAsvDb, $logDetailMatched, $logDetailsUnMatched);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetLatestBusinessDetailByMerchantId(string $merchantId): void
    {
        $merchantBusinessDetailFromAPIDb = $this->repo->merchant_business_detail->getBusinessDetailsForMerchantIdDatabase($merchantId);

        $merchantBusinessDetailFromAPIDbRawAttributes = $merchantBusinessDetailFromAPIDb->getAttributes();
        $merchantBusinessDetailFromAPIDbArray = $merchantBusinessDetailFromAPIDb->toArray();

        $merchantBusinessDetailsFromASV = $this->repo->merchant_business_detail->getBusinessDetailsForMerchantId($merchantId);
        $merchantBusinessDetailFromASVRawAttributes = $merchantBusinessDetailsFromASV->getAttributes();
        $merchantBusinessDetailFromASVArray = $merchantBusinessDetailsFromASV->toArray();


        $differenceRawAttributes = $this->comparator->getExactDifference($merchantBusinessDetailFromAPIDbRawAttributes, $merchantBusinessDetailFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantBusinessDetailFromAPIDbArray, $merchantBusinessDetailFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_BUSINESS_DETAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetLatestBusinessDetailByMerchantId",
        ];

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantBusinessDetailFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantBusinessDetailFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantBusinessDetailFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantBusinessDetailFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }
}
