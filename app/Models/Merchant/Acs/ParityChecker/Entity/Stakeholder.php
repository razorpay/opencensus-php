<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;

class Stakeholder extends Base implements ParityInterface
{
    protected $stakeholder;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->stakeholder = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkParity()
    {
        $this->checkParityStakeholder($this->merchantId);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityStakeholder(string $merchantId): void
    {
        $merchantStakeholderFromAPIDb = $this->repo->stakeholder->fetchStakeholdersDatabase($merchantId);
        $merchantStakeholderFromAPIAsv= $this->repo->stakeholder->fetchStakeholders($merchantId);

        $this->matchStakeholderByMerchantId($merchantId, $merchantStakeholderFromAPIDb, $merchantStakeholderFromAPIAsv);


        $FindOrFailStakeholderArrayFromAPIDbRawAttributes = [];
        $FindOrFailStakeholderArrayFromAPIDbArray = [];

        $FindOrFailPublicStakeholderArrayFromAPIDbRawAttributes = [];
        $FindOrFailPublicStakeholderArrayFromAPIDbArray = [];

        $FindOrFailAsvStakeholderArrayFromAPIDbRawAttributes = [];
        $FindOrFailAsvStakeholderArrayFromAPIDbArray = [];

        $FindOrFailPublicAsvStakeholderArrayFromAPIDbRawAttributes = [];
        $FindOrFailPublicAsvStakeholderArrayFromAPIDbArray = [];

        /**
         * @var StakeholderEntity $merchantStakeholder
         */
        foreach ($merchantStakeholderFromAPIDb as $merchantStakeholder) {

            $id = $merchantStakeholder->id;

            $findOrFailDb = $this->repo->stakeholder->findOrFailDatabase($id);

            /*
             *  Match address for this stakeholder
             */
            $this->matchAddressForStakeholder($merchantId, $findOrFailDb);


            $FindOrFailStakeholderArrayFromAPIDbRawAttributes[$id][] = $findOrFailDb->getAttributes();
            $FindOrFailStakeholderArrayFromAPIDbArray[$id][] = $findOrFailDb->toArray();

            $findOrFailPublicDb = $this->repo->stakeholder->findOrFailPublicDatabase($id);
            $FindOrFailPublicStakeholderArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicDb->getAttributes();
            $FindOrFailPublicStakeholderArrayFromAPIDbArray[$id][] = $findOrFailPublicDb->toArray();

            $findOrFailAsvDb = $this->repo->stakeholder->findOrFail($id);
            $FindOrFailAsvStakeholderArrayFromAPIDbRawAttributes[$id][] = $findOrFailAsvDb->getAttributes();
            $FindOrFailAsvStakeholderArrayFromAPIDbArray[$id][] = $findOrFailAsvDb->toArray();

            $findOrFailPublicAsvDb = $this->repo->stakeholder->findOrFailPublic($id);
            $FindOrFailPublicAsvStakeholderArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicAsvDb->getAttributes();
            $FindOrFailPublicAsvStakeholderArrayFromAPIDbArray[$id][] = $findOrFailPublicAsvDb->toArray();
        }


        $diffRaw_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($FindOrFailStakeholderArrayFromAPIDbRawAttributes, $FindOrFailPublicStakeholderArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($FindOrFailStakeholderArrayFromAPIDbArray, $FindOrFailPublicStakeholderArrayFromAPIDbArray);
        $logDetailMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicDb,
            Constant::FIND_OR_FAIL_API_ARRAY => $FindOrFailStakeholderArrayFromAPIDbArray,
            Constant::FIND_OR_FAIL_PUBLIC_API_ARRAY => $FindOrFailPublicStakeholderArrayFromAPIDbArray,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicDb, $diffArray_findOrFailDB_FindOrFailPublicDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($FindOrFailStakeholderArrayFromAPIDbRawAttributes, $FindOrFailAsvStakeholderArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($FindOrFailStakeholderArrayFromAPIDbArray, $FindOrFailAsvStakeholderArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailAsvDb,
            Constant::API_ENTITY_ARRAY => $FindOrFailStakeholderArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $FindOrFailStakeholderArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $FindOrFailAsvStakeholderArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $FindOrFailAsvStakeholderArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailAsvDb, $diffArray_findOrFailDB_FindOrFailAsvDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($FindOrFailStakeholderArrayFromAPIDbRawAttributes, $FindOrFailPublicAsvStakeholderArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($FindOrFailStakeholderArrayFromAPIDbArray, $FindOrFailPublicAsvStakeholderArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::API_ENTITY_ARRAY => $FindOrFailStakeholderArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $FindOrFailStakeholderArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $FindOrFailPublicAsvStakeholderArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $FindOrFailPublicAsvStakeholderArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicAsvDb, $diffArray_findOrFailDB_FindOrFailPublicAsvDb, $logDetailMatched, $logDetailsUnMatched);
    }



    private function matchAddressForStakeholder($merchantId, $stakeholder) {
        $addressAPIdb = $this->repo->address->fetchPrimaryAddressOfEntityOfType($stakeholder, "residential");
        $addressAsvDb = $this->repo->address->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");

        if($addressAPIdb != null) {
            $addressFromAPIDbRawAttributes = $addressAPIdb->getAttributes();
            $addressFromAPIDbArray = $addressAPIdb->toArray();
        } else {
            $addressFromAPIDbRawAttributes = [];
            $addressFromAPIDbArray = [];
        }

        if($addressAsvDb != null) {
            $addressFromASVRawAttributes = $addressAsvDb->getAttributes();
            $addressFromASVArray = $addressAsvDb->toArray();
        } else {
            $addressFromASVRawAttributes = [];
            $addressFromASVArray = [];
        }


        $differenceRawAttributes = $this->comparator->getExactDifference($addressFromAPIDbRawAttributes, $addressFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($addressFromAPIDbArray, $addressFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::ADDRESS,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetAddressForStakeholder",
        ];

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $addressFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $addressFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $addressFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $addressFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }

    private function matchStakeholderByMerchantId($merchantId, \RZP\Models\Base\PublicCollection $merchantStakeholderFromAPIDb, $merchantStakeholdersFromASV)
    {

        $merchantStakeholderArrayFromAPIDbRawAttributes = [];
        $merchantStakeholderArrayFromAPIDbArray = [];
        /**
         * @var stakeholderEntity $merchantStakeholder
         */
        foreach ($merchantStakeholderFromAPIDb as $merchantStakeholder) {
            $rawAttributes = $merchantStakeholder->getAttributes();
            $array = $merchantStakeholder->toArray();
            $merchantStakeholderArrayFromAPIDbRawAttributes[$rawAttributes[stakeholderEntity::ID]][] = $merchantStakeholder->getAttributes();
            $merchantStakeholderArrayFromAPIDbArray[$array[stakeholderEntity::ID]][] = $merchantStakeholder->toArray();
        }

        $merchantStakeholderArrayFromASVRawAttributes = [];
        $merchantStakeholderArrayFromASVArray = [];

        /**
         * @var stakeholderEntity $merchantStakeholder
         */
        foreach ($merchantStakeholdersFromASV as $merchantStakeholder) {
            $rawAttributes = $merchantStakeholder->getAttributes();
            $array = $merchantStakeholder->toArray();
            $merchantStakeholderArrayFromASVRawAttributes[$rawAttributes[stakeholderEntity::ID]][] = $merchantStakeholder->getAttributes();
            $merchantStakeholderArrayFromASVArray[$array[stakeholderEntity::ID]][] = $merchantStakeholder->toArray();
        }

        $differenceRawAttributes = $this->comparator->getExactDifference($merchantStakeholderArrayFromAPIDbRawAttributes, $merchantStakeholderArrayFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantStakeholderArrayFromAPIDbArray, $merchantStakeholderArrayFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::STAKEHOLDER,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForFetchStakeholderByMerchantId"
        ];

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantStakeholderArrayFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantStakeholderArrayFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantStakeholderArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantStakeholderArrayFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }


}
