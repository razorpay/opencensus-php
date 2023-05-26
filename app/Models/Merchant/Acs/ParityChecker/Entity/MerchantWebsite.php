<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Website\Entity as WebsiteEntity;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class MerchantWebsite extends Base implements ParityInterface
{
    protected $website;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->website = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkParity()
    {
        if (in_array(Constant::GET_BY_MERCHANT_ID, $this->parityCheckMethods) === true) {
            $this->checkParityForGetAllWebsiteDetailsByMerchantId($this->merchantId);
            $this->checkParityForGetLatestWebsiteByMerchantId($this->merchantId);
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
        $merchantWebsiteFromAPIDb = $this->repo->merchant_website->getAllWebsiteDetailsForMerchantId($merchantId);

        $FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes = [];
        $FindOrFailMerchantWebsiteArrayFromAPIDbArray = [];

        $FindOrFailPublicMerchantWebsiteArrayFromAPIDbRawAttributes = [];
        $FindOrFailPublicMerchantWebsiteArrayFromAPIDbArray = [];

        $FindOrFailAsvMerchantWebsiteArrayFromAPIDbRawAttributes = [];
        $FindOrFailAsvMerchantWebsiteArrayFromAPIDbArray = [];

        $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbRawAttributes = [];
        $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbArray = [];

        /**
         * @var WebsiteEntity $merchantWebsite
         */
        foreach ($merchantWebsiteFromAPIDb as $merchantWebsite) {

            $id = $merchantWebsite->getId();

            $findOrFailDb = $this->repo->merchant_website->findOrFailDatabase($id);
            $FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes[$id][] = $findOrFailDb->getAttributes();
            $FindOrFailMerchantWebsiteArrayFromAPIDbArray[$id][] = $findOrFailDb->toArray();

            $findOrFailPublicDb = $this->repo->merchant_website->findOrFailPublicDatabase($id);
            $FindOrFailPublicMerchantWebsiteArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicDb->getAttributes();
            $FindOrFailPublicMerchantWebsiteArrayFromAPIDbArray[$id][] = $findOrFailPublicDb->toArray();

            $findOrFailAsvDb = $this->repo->merchant_website->findOrFail($id);
            $FindOrFailAsvMerchantWebsiteArrayFromAPIDbRawAttributes[$id][] = $findOrFailAsvDb->getAttributes();
            $FindOrFailAsvMerchantWebsiteArrayFromAPIDbArray[$id][] = $findOrFailAsvDb->toArray();

            $findOrFailPublicAsvDb = $this->repo->merchant_website->findOrFailPublic($id);
            $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicAsvDb->getAttributes();
            $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbArray[$id][] = $findOrFailPublicAsvDb->toArray();
        }


        $diffRaw_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes, $FindOrFailPublicMerchantWebsiteArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($FindOrFailMerchantWebsiteArrayFromAPIDbArray, $FindOrFailPublicMerchantWebsiteArrayFromAPIDbArray);
        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicDb,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicDb, $diffArray_findOrFailDB_FindOrFailPublicDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes, $FindOrFailAsvMerchantWebsiteArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantWebsiteArrayFromAPIDbArray, $FindOrFailAsvMerchantWebsiteArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailAsvDb,
            Constant::API_ENTITY_ARRAY => $FindOrFailMerchantWebsiteArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $FindOrFailAsvMerchantWebsiteArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $FindOrFailAsvMerchantWebsiteArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailAsvDb, $diffArray_findOrFailDB_FindOrFailAsvDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes, $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($FindOrFailMerchantWebsiteArrayFromAPIDbArray, $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::API_ENTITY_ARRAY => $FindOrFailMerchantWebsiteArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $FindOrFailMerchantWebsiteArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $FindOrFailPublicAsvMerchantWebsiteArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicAsvDb, $diffArray_findOrFailDB_FindOrFailPublicAsvDb, $logDetailMatched, $logDetailsUnMatched);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetAllWebsiteDetailsByMerchantId(string $merchantId): void
    {
        $merchantWebsitesFromAPIDb = $this->repo->merchant_website->getAllWebsiteDetailsForMerchantId($merchantId);

        $merchantWebsiteArrayFromAPIDbRawAttributes = [];
        $merchantWebsiteArrayFromAPIDbArray = [];
        /**
         * @var WebsiteEntity $merchantWebsite
         */
        foreach ($merchantWebsitesFromAPIDb as $merchantWebsite) {
            $rawAttributes = $merchantWebsite->getAttributes();
            $array = $merchantWebsite->toArray();
            $merchantWebsiteArrayFromAPIDbRawAttributes[$rawAttributes[WebsiteEntity::ID]][] = $merchantWebsite->getAttributes();
            $merchantWebsiteArrayFromAPIDbArray[$array[WebsiteEntity::ID]][] = $merchantWebsite->toArray();
        }

        $merchantWebsitesFromASV = $this->website->getByMerchantId($merchantId);
        $merchantWebsiteArrayFromASVRawAttributes = [];
        $merchantWebsiteArrayFromASVArray = [];

        /**
         * @var WebsiteEntity $merchantWebsite
         */
        foreach ($merchantWebsitesFromASV as $merchantWebsite) {
            $rawAttributes = $merchantWebsite->getAttributes();
            $array = $merchantWebsite->toArray();
            $merchantWebsiteArrayFromASVRawAttributes[$rawAttributes[WebsiteEntity::ID]][] = $merchantWebsite->getAttributes();
            $merchantWebsiteArrayFromASVArray[$array[WebsiteEntity::ID]][] = $merchantWebsite->toArray();
        }

        $differenceRawAttributes = $this->comparator->getExactDifference($merchantWebsiteArrayFromAPIDbRawAttributes, $merchantWebsiteArrayFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantWebsiteArrayFromAPIDbArray, $merchantWebsiteArrayFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetAllWebsiteDetailsByMerchantId"
        ];

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantWebsiteArrayFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantWebsiteArrayFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantWebsiteArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantWebsiteArrayFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetLatestWebsiteByMerchantId(string $merchantId): void
    {
        $merchantWebsiteFromAPIDb = $this->repo->merchant_website->getWebsiteDetailsForMerchantIdFromDatabase($merchantId);

        $merchantWebsiteFromAPIDbRawAttributes = $merchantWebsiteFromAPIDb->getAttributes();
        $merchantWebsiteFromAPIDbArray = $merchantWebsiteFromAPIDb->toArray();

        $merchantWebsitesFromASV = $this->website->getLatestByMerchantId($merchantId);
        $merchantWebsiteFromASVRawAttributes = $merchantWebsitesFromASV->getAttributes();
        $merchantWebsiteFromASVArray = $merchantWebsitesFromASV->toArray();


        $differenceRawAttributes = $this->comparator->getExactDifference($merchantWebsiteFromAPIDbRawAttributes, $merchantWebsiteFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantWebsiteFromAPIDbArray, $merchantWebsiteFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_WEBSITE,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetLatestWebsiteByMerchantId",
        ];

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantWebsiteFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantWebsiteFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantWebsiteFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantWebsiteFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }
}
