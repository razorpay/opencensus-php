<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Email\Entity as EmailEntity;

class MerchantEmail extends Base implements ParityInterface
{
    protected $email;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->email = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkParity()
    {
        if (in_array(Constant::GET_BY_MERCHANT_ID, $this->parityCheckMethods) === true) {
            $this->checkParityForGetAllEmailsExceptPartnerDummyByMerchantId($this->merchantId);
            $merchantEmails = $this->repo->merchant_email->getEmailByMerchantId($this->merchantId);

            foreach ($merchantEmails as $merchantEmail) {
                $this->checkParityForGetEmailByTypeAndMerchantId($merchantEmail[EmailEntity::TYPE], $this->merchantId);
            }
        } else if (in_array(Constant::GET_BY_ID, $this->parityCheckMethods) === true) {
            $this->checkParityForGetById($this->merchantId);
        }
    }

    function checkParityForGetById(string $merchantId): void
    {
        $merchantEmailFromAPIDb = $this->repo->merchant_email->getEmailByMerchantId($merchantId);

        $findOrFailMerchantEmailArrayFromAPIDbRawAttributes = [];
        $findOrFailMerchantEmailArrayFromAPIDbArray = [];

        $findOrFailPublicMerchantEmailArrayFromAPIDbRawAttributes = [];
        $findOrFailPublicMerchantEmailArrayFromAPIDbArray = [];

        $findOrFailAsvMerchantEmailArrayFromAPIDbRawAttributes = [];
        $findOrFailAsvMerchantEmailArrayFromAPIDbArray = [];

        $findOrFailPublicAsvMerchantEmailArrayFromAPIDbRawAttributes = [];
        $findOrFailPublicAsvMerchantEmailArrayFromAPIDbArray = [];

        /**
         * @var EmailEntity $merchantEmail
         */
        foreach ($merchantEmailFromAPIDb as $merchantEmail) {

            $id = $merchantEmail->getId();

            $findOrFailDb = $this->repo->merchant_email->findOrFailDatabase($id);
            $findOrFailMerchantEmailArrayFromAPIDbRawAttributes[$id][] = $findOrFailDb->getAttributes();
            $findOrFailMerchantEmailArrayFromAPIDbArray[$id][] = $findOrFailDb->toArray();

            $findOrFailPublicDb = $this->repo->merchant_email->findOrFailPublicDatabase($id);
            $findOrFailPublicMerchantEmailArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicDb->getAttributes();
            $findOrFailPublicMerchantEmailArrayFromAPIDbArray[$id][] = $findOrFailPublicDb->toArray();

            $findOrFailAsvDb = $this->repo->merchant_email->findOrFail($id);
            $findOrFailAsvMerchantEmailArrayFromAPIDbRawAttributes[$id][] = $findOrFailAsvDb->getAttributes();
            $findOrFailAsvMerchantEmailArrayFromAPIDbArray[$id][] = $findOrFailAsvDb->toArray();

            $findOrFailPublicAsvDb = $this->repo->merchant_email->findOrFailPublic($id);
            $findOrFailPublicAsvMerchantEmailArrayFromAPIDbRawAttributes[$id][] = $findOrFailPublicAsvDb->getAttributes();
            $findOrFailPublicAsvMerchantEmailArrayFromAPIDbArray[$id][] = $findOrFailPublicAsvDb->toArray();
        }


        $diffRaw_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($findOrFailMerchantEmailArrayFromAPIDbRawAttributes, $findOrFailPublicMerchantEmailArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicDb = $this->comparator->getExactDifference($findOrFailMerchantEmailArrayFromAPIDbArray, $findOrFailPublicMerchantEmailArrayFromAPIDbArray);
        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantEmailArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantEmailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailAsvMerchantEmailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailAsvMerchantEmailArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicDb, $diffArray_findOrFailDB_FindOrFailPublicDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($findOrFailMerchantEmailArrayFromAPIDbRawAttributes, $findOrFailAsvMerchantEmailArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailAsvDb = $this->comparator->getExactDifference($findOrFailMerchantEmailArrayFromAPIDbArray, $findOrFailAsvMerchantEmailArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailAsvDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantEmailArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantEmailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailAsvMerchantEmailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailAsvMerchantEmailArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailAsvDb, $diffArray_findOrFailDB_FindOrFailAsvDb, $logDetailMatched, $logDetailsUnMatched);

        $diffRaw_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($findOrFailMerchantEmailArrayFromAPIDbRawAttributes, $findOrFailPublicAsvMerchantEmailArrayFromAPIDbRawAttributes);
        $diffArray_findOrFailDB_FindOrFailPublicAsvDb = $this->comparator->getExactDifference($findOrFailMerchantEmailArrayFromAPIDbArray, $findOrFailPublicAsvMerchantEmailArrayFromAPIDbArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
        ];

        $logDetailsUnMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForById_FindORFailDB_FindOrFailPublicAsvDb",
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $diffRaw_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::DIFFERENCE_ARRAY => $diffArray_findOrFailDB_FindOrFailPublicAsvDb,
            Constant::API_ENTITY_ARRAY => $findOrFailMerchantEmailArrayFromAPIDbArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $findOrFailMerchantEmailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_ARRAY => $findOrFailPublicAsvMerchantEmailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $findOrFailPublicAsvMerchantEmailArrayFromAPIDbRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($diffRaw_findOrFailDB_FindOrFailPublicAsvDb, $diffArray_findOrFailDB_FindOrFailPublicAsvDb, $logDetailMatched, $logDetailsUnMatched);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetAllEmailsExceptPartnerDummyByMerchantId(string $merchantId)
    {
        $merchantEmailsFromAPIDb = $this->repo->merchant_email->getEmailByMerchantIdFromDatabase($merchantId);

        $merchantEmailArrayFromAPIDbRawAttributes = [];
        $merchantEmailArrayFromAPIDbArray = [];

        /**
         * @var EmailEntity $merchantEnmail
         */
        foreach ($merchantEmailsFromAPIDb as $merchantEmail) {
            $rawAttributes = $merchantEmail->getAttributes();
            $array = $merchantEmail->toArray();
            $merchantEmailArrayFromAPIDbRawAttributes[$rawAttributes[EmailEntity::ID]][] = $merchantEmail->getAttributes();
            $merchantEmailArrayFromAPIDbArray[$array[EmailEntity::ID]][] = $merchantEmail->toArray();
        }

        $merchantEmailsFromASV = $this->repo->merchant_email->getEmailByMerchantId($merchantId);
        $merchantEmailArrayFromASVRawAttributes = [];
        $merchantEmailArrayFromASVArray = [];

        /**
         * @var EmailEntity $merchantEmail
         */
        foreach ($merchantEmailsFromASV as $merchantEmail) {
            $rawAttributes = $merchantEmail->getAttributes();
            $array = $merchantEmail->toArray();
            $merchantEmailArrayFromASVRawAttributes[$rawAttributes[EmailEntity::ID]][] = $merchantEmail->getAttributes();
            $merchantEmailArrayFromASVArray[$array[EmailEntity::ID]][] = $merchantEmail->toArray();
        }

        $differenceRawAttributes = $this->comparator->getExactDifference($merchantEmailArrayFromAPIDbRawAttributes, $merchantEmailArrayFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantEmailArrayFromAPIDbArray, $merchantEmailArrayFromASVArray);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::METHOD => "checkParityForGetAllEmailsExceptPartnerDummyByMerchantId"
        ];

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantEmailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantEmailArrayFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantEmailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantEmailArrayFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);

    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetEmailByTypeAndMerchantId(string $type, string $merchantId)
    {
        $merchantEmailFromAPIDb = $this->repo->merchant_email->getEmailByTypeFromDatabase($type, $merchantId);
        $merchantEmailFromASV = $this->repo->merchant_email->getEmailByType($type, $merchantId);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_EMAIL,
            Constant::MERCHANT_ID => $merchantId,
            Constant::TYPE => $type,
            Constant::METHOD => "checkParityForGetEmailByTypeAndMerchantId"
        ];

        if ($merchantEmailFromAPIDb === null or $merchantEmailFromASV === null) {

            $additionalLogDetailUnmatched = [
                Constant::API_ENTITY_ARRAY => $merchantEmailFromAPIDb?->toArray(),
                Constant::ASV_ENTITY_ARRAY => $merchantEmailFromASV?->toArray(),
            ];

            $this->compareAndLogApiAndAsvResponseForNull($merchantEmailFromAPIDb, $merchantEmailFromASV, $logDetailMatched, $additionalLogDetailUnmatched);
        }

        $merchantEmailArrayFromAPIDbRawAttributes = $merchantEmailFromAPIDb->getAttributes();
        $merchantEmailArrayFromAPIDbArray = $merchantEmailFromAPIDb->toArray();

        $merchantEmailArrayFromASVRawAttributes = $merchantEmailFromASV->getAttributes();
        $merchantEmailArrayFromASVArray = $merchantEmailFromASV->toArray();

        $differenceRawAttributes = $this->comparator->getExactDifference($merchantEmailArrayFromAPIDbRawAttributes, $merchantEmailArrayFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantEmailArrayFromAPIDbArray, $merchantEmailArrayFromASVArray);

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantEmailArrayFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantEmailArrayFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantEmailArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantEmailArrayFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }
}
