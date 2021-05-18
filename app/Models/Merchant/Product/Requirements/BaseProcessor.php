<?php

namespace RZP\Models\Merchant\Product\Requirements;

use App;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields as SelectiveRequiredFields;

class BaseProcessor
{
    /**
     * @var Detail\Core
     */
    private $merchantDetailCore;

    private $validationFields;

    /**
     * @var Document\Core
     */
    private $documentCore;

    private $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        
        $this->merchantDetailCore = new Detail\Core();

        $this->documentCore = new Document\Core();

        $this->validationFields = [];
    }

    /**
     * This is the public function exposed to fetch all the requirements for a merchant
     * Requirements are calculated based on [business_type, business_category, business_subcategory]
     * If these basic requirements are not provided by the customer, we ask for them directly through requirements
     * If basic requirements are provided, calculate and provide requirements as per these basic requirements.
     *
     * @param Merchant\Entity $merchant
     *
     * @param Product\Entity  $merchantProduct
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    public function fetchRequirements(Merchant\Entity $merchant, Product\Entity $merchantProduct)
    {
        $merchantDetails = $merchant->merchantDetail;

        $baseRequirements = [];

        foreach (Constants::BUSINESS_REQUIREMENT_FIELDS as $field)
        {
            if (empty($merchantDetails->getAttribute($field)) === true)
            {
                $requirement = [];

                $requirement[Constants::FIELD_REFERENCE] = $this->getFieldReference($field, Entity::MERCHANT);

                $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[Entity::MERCHANT][Constants::FIELD];

                $requirement[Constants::STATUS] = Constants::REQUIRED;

                $requirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;

                $baseRequirements[] = $requirement;
            }
        }

        if (count($baseRequirements) > 0)
        {
            $baseRequirements = $this->updateResolutionUrl($merchantDetails, $merchantProduct, $baseRequirements);

            return $baseRequirements;
        }

        else
        {
            $this->validationFields = $this->merchantDetailCore->getValidationFields($merchantDetails, true);

            $requirements = $this->getRequirements($merchant, $merchantDetails);

            $requirements = $this->updateResolutionUrl($merchantDetails, $merchantProduct, $requirements);

            return $requirements;
        }

    }

    /**
     * This function returns fields grouped based on type provided with allPossibleRequirements
     * returns:
     * [
     *      'fields' => [
     *          'bank_account_number => [
     *                   'field' => 'bank_account_number',
     *                   'entity => 'merchant'
     *              ],
     *              .
     *              .
     *       ],
     *      'document_fields' => [
     *          'business_pan_url => [
     *                  'field' => 'business_pan_url',
     *                  'entity => 'merchant'
     *              ],
     *              .
     *              .
     *      ]
     * ]
     *
     * @param array $allPossibleRequiredFields
     *
     * @return array
     */
    private function getRequiredFieldsByType(array $allPossibleRequiredFields): array
    {
        $validationFields = $allPossibleRequiredFields[0];

        $documentFields = [];

        $fields = [];

        foreach ($validationFields as $field)
        {
            $this->populateFieldData($field, $documentFields, $fields);
        }

        $selectiveFields = $allPossibleRequiredFields[1];

        foreach ($selectiveFields as $groupName => $group)
        {
            foreach ($group as $key1 => $set)
            {
                foreach ($set as $field)
                {
                    $this->populateFieldData($field, $documentFields, $fields);
                }
            }
        }

        $optionalFields = $allPossibleRequiredFields[2];

        foreach ($optionalFields as $field)
        {
            $this->populateFieldData($field, $documentFields, $fields);
        }

        $response = [];

        $response[Constants::DOCUMENT_FIELDS] = $documentFields;

        $response[Constants::FIELDS] = $fields;

        return $response;
    }

    /**
     * This function returns fields grouped based on type provided from pending verification fields
     * [
     *      'fields' => [
     *          'bank_account_number => [
     *                   'field' => 'bank_account_number',
     *                   'entity => 'merchant'
     *              ],
     *              .
     *              .
     *       ],
     *      'document_fields' => [
     *          'business_pan_url => [
     *                  'field' => 'business_pan_url',
     *                  'entity => 'merchant'
     *              ],
     *              .
     *              .
     *      ]
     * ]
     *
     * @param array $requiredFields
     *
     * @return array
     */
    private function getRequiredFieldsByTypeFromPendingVerificationFields(array $requiredFields): array
    {
        $documentFields = [];

        $fields = [];

        foreach ($requiredFields as $field)
        {
            $this->populateFieldData($field, $documentFields, $fields);
        }

        $response = [];

        $response[Constants::DOCUMENT_FIELDS] = $documentFields;

        $response[Constants::FIELDS] = $fields;

        return $response;
    }

    /**
     * This function returns both document requirements and field requirements based on form submission
     * Eg:
     *  [{
     *       "field_reference": "business_proof_of_identification.business_pan_url", // document requirement
     *       "resolution_url": "/accounts/{accountId}/documents",
     *       "status": "required",
     *       "reason_code": "document_missing"
     *   },
     *   {
     *       "field_reference": "legal_info.pan",  // field requirement
     *       "resolution_url": "/accounts",
     *       "status": "required",
     *      "reason_code": "field_missing"
     *   }]
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    private function getRequirements(Merchant\Entity $merchant, Detail\Entity $merchantDetails): array
    {
        $requirements = [];

        $verificationResponse = [];

        if ($merchantDetails->isSubmitted() === false)
        {
            $verificationResponse = $this->merchantDetailCore->setVerificationDetails($merchantDetails, $merchant, $verificationResponse, true);

            if ($verificationResponse['can_submit'] === true)
            {
                return [];
            }
            else
            {
                $allRequiredFields = $verificationResponse['verification']['required_fields'];

                $requirementsByType = $this->getRequiredFieldsByTypeFromPendingVerificationFields($allRequiredFields);

                $documentFieldRequirements = $this->getDocumentFieldRequirements($merchant, $merchantDetails, $requirementsByType[Constants::DOCUMENT_FIELDS], false);

                $fieldRequirements = $this->getFieldRequirements($merchant, $merchantDetails, $requirementsByType[Constants::FIELDS]);

                $requirements = array_merge($requirements, $documentFieldRequirements, $fieldRequirements);

            }
        }
        else if($merchantDetails->getActivationStatus() === Detail\Status::NEEDS_CLARIFICATION)
        {
            $requiredFields = $this->validationFields;

            $requirementsByType = $this->getRequiredFieldsByType($requiredFields);

            $documentFieldRequirements = $this->getDocumentFieldRequirements($merchant, $merchantDetails, [], true);

            $fieldRequirements = $this->getFieldRequirements($merchant, $merchantDetails, $requirementsByType[Constants::FIELDS]);

            $requirements = array_merge($requirements, $documentFieldRequirements, $fieldRequirements);
        }

        return $requirements;
    }


    /**
     * This function is a driver function to evaluate documents required for the account
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param array           $fields
     * @param bool            $submitted
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    private function getDocumentFieldRequirements(Merchant\Entity $merchant, Detail\Entity $merchantDetails, array $fields, bool $submitted): array
    {
        $requirements = [];

        $documentByType = $this->documentCore->documentResponse($merchant);

        //$documentByType = [];

        //foreach ($documents as $document)
        //{
        //    $documentByType[$document->getDocumentType()] = $document;
        //}

        $missingDocuments = ($submitted === true) ? [] : array_diff_key($fields, $documentByType);

        $submittedDocuments = ($submitted === true) ? $this->getRequiredFieldsByTypeFromPendingVerificationFields(array_keys($documentByType)) : [];

        $submittedDocuments = $submittedDocuments[Constants::DOCUMENT_FIELDS] ?? [];

        $missingDocumentRequirements = $this->getMissingDocumentRequirements($merchantDetails, $missingDocuments);

        $documentRequirementsFromSubmittedDocuments = $this->getDocumentRequirementsFromSubmittedDocuments($merchant, $merchantDetails, $submittedDocuments);

        $requirements = array_merge($requirements, $missingDocumentRequirements, $documentRequirementsFromSubmittedDocuments);

        return $requirements;
    }

    /**
     * This function returns missing document requirements based on [business_type, business_category,
     * business_subcategory]
     *
     *------------------------------- Before form submission -------------------------------
     *
     * If documents required are validation required fields, the requirement is as follows
     * Eg: Public ltd business
     *  [{
     *       "field_reference": "business_proof_of_identification.business_proof_url",
     *       "resolution_url": "/accounts/{accountId}/documents",
     *       "status": "required",
     *       "reason_code": "document_missing"
     *   },
     *   {
     *       "field_reference": "business_proof_of_identification.business_pan_url",
     *       "resolution_url": "/accounts/{accountId}/documents",
     *       "status": "required",
     *       "reason_code": "document_missing"
     *   }]
     *
     * If documents required are validation selective required fields, the requirement is as follows
     * Eg: Propertership business
     * Business_proof_documents: [gstin, msme, shop_establishment_certificate] can be either of these
     *  [{
     *       "field_reference": "business_proof_of_identification",
     *       "resolution_url": "/accounts/{accountId}/documents",
     *       "status": "required",
     *       "reason_code": "document_missing"
     *   },
     *   {
     *       "field_reference": "individual_proof_of_identification",
     *       "resolution_url":"/accounts/{accountId}/stakeholders/{stakeholderId}/documents",
     *       "status": "required",
     *       "reason_code": "document_missing"
     *  }]
     *
     * ------------------------------- After form submission -------------------------------
     *
     * 1. We fetch the submitted documents
     *   If the document has status associated to it and it has negative status(incorrect_details, not_matched), we
     *   show up the doc again in requirements
     *
     * @param Detail\Entity $merchantDetails
     * @param array         $requiredDocuments
     *
     * @return array
     */
    private function getMissingDocumentRequirements(Detail\Entity $merchantDetails, array $requiredDocuments): array
    {
        $missingDocumentRequirements = [];

        foreach ($requiredDocuments as $field => $fieldData)
        {
            $documentType = $fieldData[Constants::FIELD];

            if ($this->isSelectiveRequiredProof($documentType, $merchantDetails) === false)
            {
                $requirement = [Constants::FIELD_REFERENCE => Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$documentType] . '.' . $documentType];
            }
            else
            {
                $requirement = [Constants::FIELD_REFERENCE => Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$documentType]];
            }

            $entity = $fieldData[Constants::ENTITY];

            $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[$entity][Constants::DOCUMENT];

            $requirement[Constants::STATUS] = Constants::REQUIRED;

            $requirement[Constants::REASON_CODE] = Constants::DOCUMENT_MISSING;

            $missingDocumentRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
        }

        return array_values($missingDocumentRequirements);
    }

    /**
     * This function returns document required fields after L2 form submission.
     * If document uploaded contain a status key associated to it and has some negative verification status, then it
     * shows up in requirements.
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param array           $submittedDocuments
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    private function getDocumentRequirementsFromSubmittedDocuments(Merchant\Entity $merchant, Detail\Entity $merchantDetails, array $submittedDocuments): array
    {
        $requirementsFromSubmittedDocuments = [];

        foreach ($submittedDocuments as $documentType => $fieldData)
        {
            $requirement = [Constants::FIELD_REFERENCE => Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$documentType] . '.' . $documentType];

            $entity = $fieldData[Constants::ENTITY];

            $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[$entity][Constants::DOCUMENT];

            $statusKey = $this->getArtefactStatusUpdateKey($documentType);

            if ($statusKey !== Constants::NOT_APPLICABLE)
            {
                $reasonCode = $this->getDocumentValidationReason($merchantDetails->getAttribute($statusKey));

                if (empty($reasonCode) === false)
                {
                    $requirement[Constants::REASON_CODE] = $reasonCode;

                    $requirement[Constants::STATUS] = Constants::REQUIRED;

                    $requirementsFromSubmittedDocuments[] = $requirement;
                }
            }
        }

        return $requirementsFromSubmittedDocuments;
    }

    /**
     * This function returns list of field requirements from list of fields required
     * $fields - [
     *      'bank_account_number => [
     *          'field' => 'bank_account_number',
     *          'entity => 'merchant'
     *      ],
     *      'name => [
     *          'field' => 'name',
     *          'entity => 'stakeholder'
     *      ],
     * ]
     * Based on the entity and type, url is resolved from
     * \RZP\Models\Merchant\Product\Requirements\Constants::ENTITY_RESOLUTION_URL_MAPPING
     *
     * Entities here are merchant_details and stakeholder
     * If field value is null       - field_missing
     * if field value is non-null
     *    case 1: there is no status associated to the field
     *            eg: bank_branch_ifsc.
     *    case 2: there is a status associated to the field
     *            eg: bank_account_number
     *            If status associated with field is needed to be re-submitted, then it shows up in requirements.
     *            (Happens after L2 form submission)
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetail
     * @param array           $fields
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    private function getFieldRequirements(Merchant\Entity $merchant, Detail\Entity $merchantDetail, array $fields): array
    {
        $requirements = [];

        $stakeholderExists = (new Stakeholder\Core())->checkIfStakeholderExists($merchantDetail);

        $stakeholder = null;

        if ($stakeholderExists === true)
        {
            $stakeholder = (new Stakeholder\Core())->createOrFetchStakeholder($merchantDetail);
        }

        foreach ($fields as $field => $fieldData)
        {
            $resolutionUrlKey = $this->getResolutionUrlKey($stakeholderExists, $fieldData);

            $entityStr = $fieldData[Constants::ENTITY];

            $fieldReference = $this->getFieldReference($field, $entityStr);

            $requirement = [Constants::FIELD_REFERENCE => $fieldReference];

            if (in_array($field, Constants::SETTLEMENT_FIELDS))
            {
                $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[Util\Constants::CHECKOUT][Constants::FIELD];
            }
            else
            {
                $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[$resolutionUrlKey][Constants::FIELD];
            }

            $entity = $merchantDetail;

            if ($entityStr === Entity::STAKEHOLDER)
            {
                $entity = $stakeholder;
            }

            $fieldValue = null;

            if (empty($entity) === false)
            {
                $fieldValue = $entity->getAttribute($field);;
            }

            if (is_bool($fieldValue) === false && empty($fieldValue) === true)
            {
                $requirement[Constants::STATUS] = Constants::REQUIRED;

                $requirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;

                $requirements[] = $requirement;

            }
            else
            {
                $statusKey = $this->getArtefactStatusUpdateKey($field);

                if ($statusKey !== Constants::NOT_APPLICABLE)
                {
                    $status = $entity->getAttribute($statusKey);

                    if (empty($status) === false && in_array($status, Constants::INTERNAL_STATUS) === false)
                    {
                        $reasonCode = $this->getFieldValidationReason($status);

                        $requirement[Constants::STATUS] = Constants::REQUIRED;

                        $requirement[Constants::REASON_CODE] = $reasonCode;

                        $requirements[] = $requirement;
                    }

                }

            }
        }

        return $requirements;
    }

    private function getResolutionUrlKey(bool $stakeholderExists, array $fieldData): string
    {
        $entityStr = $fieldData[Constants::ENTITY];

        if ($entityStr === Entity::STAKEHOLDER && $stakeholderExists === false)
        {
            $entityStr = $entityStr . Constants::CREATE;
        }

        if ($entityStr === Entity::STAKEHOLDER && $stakeholderExists === true)
        {
            $entityStr = $entityStr . Constants::UPDATE;
        }

        return $entityStr;
    }

    private function getDocumentValidationReason($status): string
    {
        $reasonCode = '';

        if ($status === BvsValidation\Constants::INCORRECT_DETAILS)
        {
            $reasonCode = Constants::DOCUMENT_INVALID;
        }

        if ($status === BvsValidation\Constants::NOT_MATCHED)
        {
            $reasonCode = Constants::DOCUMENT_DETAILS_MISMATCH;
        }

        return $reasonCode;
    }

    private function getFieldValidationReason(string $status): string
    {
        $reasonCode = '';

        if ($status === BvsValidation\Constants::INCORRECT_DETAILS)
        {
            $reasonCode = Constants::FIELD_INVALID;
        }
        else
        {
            if ($status === BvsValidation\Constants::NOT_MATCHED)
            {
                $reasonCode = Constants::FIELD_MISMATCH;
            }
        }

        return $reasonCode;
    }

    /**
     * This function returns the documentUpdateStatusKey for a document or field in merchant_details that gets updated
     * in KYC verification Eg: field                    - bank_account_number(identifier) artefact_type            -
     * bank_account documentTypeStatusKey    - bank_details_verification_status
     *
     * field                    - aadhar_front (document)
     * artefact_type            - aadhar
     * documentTypeStatusKey    - poa_verification_status
     *
     * @param string $field
     *
     * @return string
     */
    private function getArtefactStatusUpdateKey(string $field): string
    {
        return Constants::ARTEFACT_STATUS_MAPPING[$field] ?? Constants::NOT_APPLICABLE;
    }

    private function getFieldReference(string $fieldName, string $entity): string
    {
        $fieldReference = $entity . '->' . $fieldName;

        $entityFieldMapping = FieldMapping::FIELD_MAPPING[$entity] ?? [];

        if (empty($entityFieldMapping) === false)
        {
            $fieldReference = $entityFieldMapping[$fieldName] ?? $fieldReference;
        }

        return $fieldReference;
    }

    /**
     * This function populates the corresponding $documentFields or $fields from a given field
     * populates the following into respective group. document_fields or fields
     * Eg:
     * [
     *  'bank_account_number => [
     *      'field' => 'bank_account_number',
     *      'entity => 'merchant'
     * ]
     *
     *
     * @param string $field
     * @param array  $documentFields
     * @param array  $fields
     */
    private function populateFieldData(string $field, array &$documentFields, array &$fields): void
    {
        $fieldData = [];

        if (Document\Type::isValid($field))
        {
            $proofType = Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$field];

            $entity = Document\Type::PROOF_TYPE_ENTITY_MAPPING[$proofType];

            $fieldData[Constants::FIELD] = $field;

            $fieldData[Constants::ENTITY] = $entity;

            $documentFields[$field] = $fieldData;
        }
        else
        {
            if (array_key_exists($field, Constants::STAKEHOLDER_MERCHANT_DETAILS_MAPPING))
            {
                $entity = Entity::STAKEHOLDER;

                $field = Constants::STAKEHOLDER_MERCHANT_DETAILS_MAPPING[$field];
            }
            else
            {
                $entity = Entity::MERCHANT;
            }

            $fieldData[Constants::FIELD] = $field;

            $fieldData[Constants::ENTITY] = $entity;

            $fields[$field] = $fieldData;
        }
    }

    /**
     *
     * SelectiveRequiredProof : For a particular proof_type, user can submit one set of proofs from multiple sets of
     * proofs Eg: POA documents -> Currently for all BusinessType these are selectiveRequiredProof
     *
     * Special case: For PROPRIETORSHIP businessType
     * Business Proof documents are selective required fields
     *                  (MSME, GSTIN, SHOP_ESTABLISHMENT_CERTIFICATE)
     *
     * @param string        $documentType
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     */
    private function isSelectiveRequiredProof(string $documentType, Detail\Entity $merchantDetails): bool
    {
        $isUnRegisteredBusiness = Detail\BusinessType::isUnregisteredBusiness($merchantDetails->getBusinessType());

        if ($isUnRegisteredBusiness === true)
        {
            $poaDocuments = SelectiveRequiredFields::UNREGISTERED_POA_FIELDS[SelectiveRequiredFields::POA_DOCUMENTS];
        }
        else
        {
            $poaDocuments = SelectiveRequiredFields::REGISTERED_POA_FIELDS[SelectiveRequiredFields::POA_DOCUMENTS];
        }

        $isSelectiveRequiredProof = false;

        foreach ($poaDocuments as $poaDocumentGroup)
        {
            $isSelectiveRequiredProof = (in_array($documentType, $poaDocumentGroup) === true || $isSelectiveRequiredProof);
        }

        if ($isSelectiveRequiredProof === false && $merchantDetails->getBusinessType() === Detail\BusinessType::PROPRIETORSHIP)
        {
            $selectiveRequiredFields = $this->validationFields[1];

            foreach ($selectiveRequiredFields as $groupName => $group)
            {
                foreach ($group as $key1 => $set)
                {
                    $isSelectiveRequiredProof = (in_array($documentType, $set) === true || $isSelectiveRequiredProof);
                }
            }
        }

        return $isSelectiveRequiredProof;
    }

    private function updateResolutionUrl(Detail\Entity $merchantDetails, Product\Entity $merchantProduct, array $requirements): array
    {
        foreach ($requirements as & $requirement)
        {
            $url = $requirement[Constants::RESOLUTION_URL];

            $requirement[Constants::RESOLUTION_URL] = $this->getResolutionUrl($merchantDetails, $merchantProduct, $url);
        }

        return $requirements;
    }

    private function getResolutionUrl(Detail\Entity $merchantDetails, Product\Entity $merchantProduct, string $url): string
    {
        if ($this->app['env'] === Environment::TESTING)
        {
            return $url;
        }

        $stakeholder = $merchantDetails->stakeholder;

        if (empty($stakeholder) === false)
        {
            $url = str_replace(Constants::STAKEHOLDER_ID_PLACEHOLDER, $stakeholder->getPublicId(), $url);
        }

        $url = str_replace(Constants::ACCOUNT_ID_PLACEHOLDER, Account\Entity::getSignedId($merchantDetails->getMerchantId()), $url);

        $url = str_replace(Constants::MERCHANT_PRODUCT_ID_PLACEHOLDER, $merchantProduct->getPublicId(), $url);

        return $url;
    }
}
