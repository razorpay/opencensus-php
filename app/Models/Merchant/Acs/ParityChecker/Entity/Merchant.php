<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use Exception;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Merchant extends Base implements ParityInterface
{
    protected $merchant;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->merchant = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\Merchant();
    }

    /**
     * @throws \Exception
     */
    public function checkReadParity()
    {
        $this->checkParityForGetById($this->merchantId);
    }

    /**
     * @throws \Exception
     */
    function checkParityForGetById(string $merchantId): void
    {
        $merchantForFindOrFailFromAPIDb = $this->repo->merchant->findOrFailDatabase($merchantId);
        $merchantForFindOrFailFromASV = $this->repo->merchant->findOrFail($merchantId);

        $merchantForFindOrFailPublicFromAPIDb = $this->repo->merchant->findOrFailPublicDatabase($merchantId);
        $merchantForFindOrFailPublicFromASV = $this->repo->merchant->findOrFailPublic($merchantId);


        $findOrFailMerchantFromAPIDbRawAttributes = $merchantForFindOrFailFromAPIDb->getAttributes();
        $findOrFailMerchantFromAPIDbArray = $merchantForFindOrFailFromAPIDb->toArray();

        $findOrFailPublicMerchantFromAPIDbRawAttributes = $merchantForFindOrFailPublicFromAPIDb->getAttributes();
        $findOrFailPublicMerchantFromAPIDbArray = $merchantForFindOrFailPublicFromAPIDb->toArray();

        $findOrFailMerchantFromASVRawAttributes = $merchantForFindOrFailFromASV->getAttributes();
        $findOrFailMerchantFromASVArray = $merchantForFindOrFailFromASV->toArray();

        $findOrFailPublicMerchantFromASVRawAttributes = $merchantForFindOrFailPublicFromASV->getAttributes();
        $findOrFailPublicMerchantFromASVArray = $merchantForFindOrFailPublicFromASV->toArray();


        $diffRaw_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($findOrFailMerchantFromAPIDbRawAttributes, $findOrFailPublicMerchantFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($findOrFailMerchantFromAPIDbArray, $findOrFailPublicMerchantFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetById_FindORFailDB_FindOrFailPublicDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicDb,
            Constant::API_ENTITY_ARRAY . '_findOrFail' => $findOrFailMerchantFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES . '_findOrFail' => $findOrFailMerchantFromAPIDbRawAttributes,
            Constant::API_ENTITY_ARRAY . '_findOrFailPublic' => $findOrFailPublicMerchantFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES . '_findOrFailPublic' => $findOrFailPublicMerchantFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicDb, $diffArray_findOrFailDB_FindOrFailPublicDb, $logDetailMatched, $logDetailsUnMatched);


        $diffRaw_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($findOrFailMerchantFromAPIDbRawAttributes, $findOrFailMerchantFromASVRawAttributes);
        $diffArray_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($findOrFailMerchantFromAPIDbArray, $findOrFailMerchantFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailAsvDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailMerchantFromASVArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantFromASVRawAttributes
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailAsvDb, $diffArray_findOrFailDB_FindOrFailAsvDb, $logDetailMatched, $logDetailsUnMatched);


        $diffRaw_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($findOrFailMerchantFromAPIDbRawAttributes, $findOrFailPublicMerchantFromASVRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($findOrFailMerchantFromAPIDbArray, $findOrFailPublicMerchantFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailPublicMerchantFromASVArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailPublicMerchantFromASVRawAttributes
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicAsvDb, $diffArray_findOrFailDB_FindOrFailPublicAsvDb, $logDetailMatched, $logDetailsUnMatched);
    }
}
