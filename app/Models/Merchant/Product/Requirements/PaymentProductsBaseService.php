<?php

namespace RZP\Models\Merchant\Product\Requirements;

use App;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Document;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Product\TncMap\Acceptance as TncAcceptance;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields as SelectiveRequiredFields;

class PaymentProductsBaseService extends Base\Service
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

    private $tncCore;

    private $otpCore;

    /**
     * @var Detail\NeedsClarification\Core
     */
    private $clarificationCore;

    public function __construct()
    {
        parent::__construct();

        $this->merchantDetailCore = new Detail\Core();

        $this->documentCore = new Document\Core();

        $this->clarificationCore = new Detail\NeedsClarification\Core();

        $this->validationFields = [];

        $this->tncCore = new TncAcceptance\Core();

        $this->otpCore = new Product\Otp\Core();

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
     * @throws LogicException
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
            [$requirements, $optionalRequirements] = $this->getRequirements($merchant, $merchantDetails, $merchantProduct);

            $allRequirements = array_merge($requirements, $optionalRequirements);

            $allRequirements = $this->updateResolutionUrl($merchantDetails, $merchantProduct, $allRequirements);

            $this->addInstantActivationAlertInRequirementIfApplicable($merchant, $allRequirements);

            return $allRequirements;
        }

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
     * @param Detail\Entity $merchantDetails
     * @param Product\Entity $merchantProduct
     *
     * @return array
     * @throws LogicException
     */
    public function getRequirements(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Product\Entity $merchantProduct): array
    {
        $this->validationFields = $this->merchantDetailCore->getValidationFields($merchantDetails, true);

        $requirements = [];

        $optionalRequirements = [];

        $verificationResponse = [];

        [$tncRequirement, $tncIpRequirement] = $this->getTncRequirements($merchant);

        $this->getOtpVerificationLogRequirements($requirements, $merchant);

        $isNoDocEnabledAndGmvLimitExhausted = (new AccountV2\Core())->isNoDocEnabledAndGmvLimitExhausted($merchant);

        if (empty($tncRequirement) === false)
        {
            array_push($requirements, $tncRequirement);

            if(empty($tncIpRequirement) === false)
            {
                array_push($requirements, $tncIpRequirement);
            }
        }

        if ($merchantDetails->isSubmitted() === false or $merchantDetails->getActivationStatus() === Detail\Status::ACTIVATED_KYC_PENDING)
        {
            $verificationResponse = $this->merchantDetailCore->setVerificationDetails($merchantDetails, $merchant, $verificationResponse, true);

            if ($verificationResponse['can_submit'] === true)
            {
                if($merchant->isNoDocOnboardingEnabled() === true)
                {
                    $allOptionalFields = $verificationResponse['verification']['optional_fields'];

                    [$documentFieldRequirements, $fieldRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements] = $this->getDocAndNonDocFieldRequirements([], $merchant, false, [], $allOptionalFields);

                    $optionalRequirements = array_merge($optionalRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements);
                }

                return [$requirements, $optionalRequirements];
            }
            else
            {
                $allRequiredFields = $verificationResponse['verification']['required_fields'];

                $allOptionalFields = $verificationResponse['verification']['optional_fields'];

                [$documentFieldRequirements, $fieldRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements] = $this->getDocAndNonDocFieldRequirements($allRequiredFields, $merchant, false, [], $allOptionalFields);

                $requirements = array_merge($requirements, $documentFieldRequirements, $fieldRequirements);

                $optionalRequirements = array_merge($optionalRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements);
            }
        }
        else if ($merchantDetails->getActivationStatus() === Detail\Status::NEEDS_CLARIFICATION)
        {
            if ($isNoDocEnabledAndGmvLimitExhausted === true)
            {
                $verificationResponse = $this->merchantDetailCore->setVerificationDetails($merchantDetails, $merchant, $verificationResponse, true);

                $allRequiredFields = $verificationResponse['verification']['required_fields'];

                [$documentFieldRequirements, $fieldRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements] = $this->getDocAndNonDocFieldRequirements($allRequiredFields, $merchant, true, [], []);

                $requirements = array_merge($requirements, $documentFieldRequirements, $fieldRequirements);

                foreach ($requirements as & $requirement)
                {
                    $requirement[Constants::STATUS] = Constants::REQUIRED;

                    $requirement[Constants::DESCRIPTION] = Detail\NeedsClarificationReasonsList::GMV_LIMIT_BREACHED_FOR_NO_DOC_ONBOARDING;
                }
            }
            else
            {
                $nonAcknowledgedNCFields = $this->clarificationCore->getNonAcknowledgedNCFields($merchant, $merchantDetails);

                $clarificationReasons = $this->getFormattedNonAcknowledgedNCFields($nonAcknowledgedNCFields, $merchantDetails);

                $ncFields = $clarificationReasons[Constants::FIELDS] ?? [];

                [$documentFieldRequirements, $fieldRequirements] = $this->getDocAndNonDocFieldRequirements(array_keys($ncFields), $merchant, true, $clarificationReasons, []);

                $requirements = array_merge($requirements, $documentFieldRequirements, $fieldRequirements);
            }
        }

        if($merchant->isNoDocOnboardingEnabled() === true and $isNoDocEnabledAndGmvLimitExhausted == false and empty($optionalRequirements) == true)
        {
            $verificationResponse = $this->merchantDetailCore->setVerificationDetails($merchantDetails, $merchant, $verificationResponse, true);

            $allOptionalFields = $verificationResponse['verification']['optional_fields'];

            [$documentFieldRequirements, $fieldRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements] = $this->getDocAndNonDocFieldRequirements([], $merchant, false, [], $allOptionalFields);

            $optionalRequirements = array_merge($optionalRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements);
        }

        $this->trace->info(TraceCode::MERCHANT_PRODUCT_ALL_REQUIREMENTS,
                           [
                               'merchant_id'           => $merchant->getId(),
                               'requirements'          => $requirements,
                               'merchant_product_id'   => $merchantProduct->getId(),
                               'merchant_product_name' => $merchantProduct->getProduct(),
                               '$optionalRequirements' => $optionalRequirements,
                           ]);

        return [$requirements, $optionalRequirements];
    }

    protected function getTncRequirements(Merchant\Entity $merchant): array
    {
        $hasPendingTnc = $this->tncCore->hasPendingTnc($merchant, Product\Name::ALL);

        $requirement = [];

        $ipRequirement = [];

        if ($hasPendingTnc === true)
        {
            $requirement[Constants::FIELD_REFERENCE] = Constants::TNC_ACCEPTED;

            $requirement[Constants::RESOLUTION_URL] = Constants::PAYMENT_CONFIG_RESOLUTION_URL;

            $requirement[Constants::STATUS] = Constants::REQUIRED;

            $requirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;

            if($merchant->isNoDocOnboardingEnabled() or ($merchant->isLinkedAccount() === false
                                 and $this->isSubmerchantsPartnerExcludedFromProvidingIp($merchant->getId()) === false))
            {
                $ipRequirement[Constants::FIELD_REFERENCE] = Constants::IP;

                $ipRequirement[Constants::RESOLUTION_URL] = Constants::PAYMENT_CONFIG_RESOLUTION_URL;

                $ipRequirement[Constants::STATUS] = Constants::REQUIRED;

                $ipRequirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;
            }
        }

        return [$requirement,$ipRequirement];
    }

    private function getFormattedNonAcknowledgedNCFields(array $nonRespondedFields, Detail\Entity $merchantDetails): array
    {
        $clarificationReasons = $this->clarificationCore->getFormattedKycClarificationReasons(
            $merchantDetails->getKycClarificationReasons());

        $ncFields = $clarificationReasons[Constants::FIELDS] ?? [];

        $ncDocuments = $clarificationReasons[Constants::DOCUMENTS] ?? [];

        $ncFields = array_intersect_key($ncFields, $nonRespondedFields[NeedsClarification\Constants::FIELDS]);

        $ncDocuments = array_intersect_key($ncDocuments, $nonRespondedFields[NeedsClarification\Constants::DOCUMENTS]);

        $bankProofFormattedReasons = $this->getFormattedBankProofNCFields($nonRespondedFields, $ncFields, $clarificationReasons);

        if( empty($bankProofFormattedReasons) === false)
        {
            $ncDocuments = array_merge($ncDocuments, $bankProofFormattedReasons);
        }

        $pendingNCFields = [];

        $pendingNCFields[Constants::FIELDS] = $ncFields;

        $pendingNCFields[Constants::DOCUMENTS] = $ncDocuments;

        $this->trace->info(TraceCode::MERCHANT_FORMATTED_NON_ACKNOWLEDGED_NC_FIELDS, $pendingNCFields);

        return $pendingNCFields;
    }

    /**
     * @param array $nonAcknowledgedNCFields      -- non acknowledged NC fields
     * @param array $ncFormattedFieldRequirements -- Formatted reasons for $nonAcknowledgedNCFields
     * @param array $allLatestNCFormattedReasons  -- Formatted reasons for all the latest NC marked fields
     *
     * Case 1: Latest Bank NC fields are acknowledged but bank proof is not submitted
     *         $ncFormattedFieldRequirements will not contain any formatted reasons related to Bank NC fields
     * Case 2: Latest Bank NC fields are not acknowledged and bank proof is not submitted
     * @return array
     */
    private function getFormattedBankProofNCFields(array $nonAcknowledgedNCFields, array $ncFormattedFieldRequirements, array $allLatestNCFormattedReasons): array
    {
        if (count($nonAcknowledgedNCFields[Constants::DOCUMENTS]) === 0)
        {
            return [];
        }

        if (array_key_exists(Document\Type::CANCELLED_CHEQUE, $nonAcknowledgedNCFields[Constants::DOCUMENTS]) === true)
        {
            $bankNCFields = array_intersect(array_keys($ncFormattedFieldRequirements), Detail\Constants::BANK_DETAIL_FIELDS);

            if (empty($bankNCFields) === true)
            {
                //Pick the formatted reason from any of the latest acknowledged bank NC field
                $acknowledgedLatestBankNCFields = array_intersect(array_keys($allLatestNCFormattedReasons[Constants::FIELDS]), Detail\Constants::BANK_DETAIL_FIELDS);
                $bankNCField                    = array_values($acknowledgedLatestBankNCFields)[0];
                $formattedRequirement           = $allLatestNCFormattedReasons[Constants::FIELDS][$bankNCField];
            }
            else
            {
                // Pick the formatted reason from any of the nonAcknowledged bank nc field
                $bankNCField          = array_values($bankNCFields)[0];
                $formattedRequirement = $ncFormattedFieldRequirements[$bankNCField];
            }

            return [
                Document\Type::CANCELLED_CHEQUE => $formattedRequirement
            ];
        }

        return [];
    }

    /**
     * This function fetches document field and non-document field requirements
     *
     * @param array $requiredFields
     * @param Merchant\Entity $merchant
     * @param bool $isSubmitted
     * @param array $clarificationReasons
     *
     * @return array
     * @throws LogicException
     */
    private function getDocAndNonDocFieldRequirements(array $requiredFields, Merchant\Entity $merchant, bool $isSubmitted, array $clarificationReasons, array $optionalFields): array
    {
        $merchantDetails = $merchant->merchantDetail;

        $requirementsByType = $this->getRequiredFieldsByTypeFromPendingVerificationFields($requiredFields);

        $optionalRequirementsByType = $this->getRequiredFieldsByTypeFromPendingVerificationFields($optionalFields);

        $requirementsByTypeDocument = $requirementsByType[Constants::DOCUMENT_FIELDS] ?? [];

        $optionalRequirementsByTypeDocument = $optionalRequirementsByType[Constants::DOCUMENT_FIELDS] ?? [];

        if ($merchantDetails->getActivationStatus() === Detail\Status::NEEDS_CLARIFICATION and $merchant->isNoDocOnboardingEnabled() === false)
        {
            $requirementsByTypeDocument = [];
        }

        [$documentFieldRequirements, $optionalDocumentFieldRequirements] = $this->getDocumentFieldRequirements($merchant, $merchantDetails, $requirementsByTypeDocument, $isSubmitted, $clarificationReasons, $optionalRequirementsByTypeDocument);

        $requirementsByTypeField = $requirementsByType[Constants::FIELDS] ?? [];

        $optionalRequirementsByTypeField = $optionalRequirementsByType[Constants::FIELDS] ?? [];

        [$fieldRequirements, $optionalFieldRequirements] = $this->getFieldRequirements($merchantDetails, $requirementsByTypeField, $clarificationReasons, $optionalRequirementsByTypeField);

        return [$documentFieldRequirements, $fieldRequirements, $optionalDocumentFieldRequirements, $optionalFieldRequirements];
    }

    /**
     * This function is a driver function to evaluate documents required for the account
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param array           $fields
     * @param bool            $submitted
     * @param array           $clarificationReasons
     *
     * @return array
     * @throws LogicException
     */
    private function getDocumentFieldRequirements(Merchant\Entity $merchant, Detail\Entity $merchantDetails, array $fields, bool $submitted, array $clarificationReasons, array $optionalFields): array
    {
        $requirements = [];

        $missingDocumentRequirements = [];

        $missingOptionalDocumentRequirements = [];

        $documentRequirementsFromSubmittedDocuments = [];

        $isNoDocEnabledAndGmvLimitExhausted = (new AccountV2\Core())->isNoDocEnabledAndGmvLimitExhausted($merchant);

        $getDocReqAfterNoDocLimitBreach = false;

        if ($merchantDetails->getActivationStatus() === Detail\Status::NEEDS_CLARIFICATION and $isNoDocEnabledAndGmvLimitExhausted === true)
        {
            $getDocReqAfterNoDocLimitBreach = true;
        }

        if ($submitted === false or $getDocReqAfterNoDocLimitBreach === true)
        {
            $documentByType = $this->documentCore->documentResponse($merchant);

            $missingDocuments = array_diff_key($fields, $documentByType);

            $missingOptionalDocuments = array_diff_key($optionalFields, $documentByType);

            [$missingDocumentRequirements, $missingOptionalDocumentRequirements] = $this->getMissingDocumentRequirements($merchantDetails, $missingDocuments, $missingOptionalDocuments);
        }
        else
        {
            $ncFormattedDocumentReasons = $clarificationReasons[Constants::DOCUMENTS] ?? [];

            $ncDocumentsFieldData = $this->getRequiredFieldsByTypeFromPendingVerificationFields(array_keys($ncFormattedDocumentReasons));

            $ncDocumentsFieldData = $ncDocumentsFieldData[Constants::DOCUMENT_FIELDS] ?? [];

            $documentRequirementsFromSubmittedDocuments = $this->getNCDocumentRequirements($merchantDetails, $ncDocumentsFieldData, $ncFormattedDocumentReasons);

        }

        $this->trace->info(TraceCode::MERCHANT_DOCUMENT_REQUIREMENTS, [
            'merchant_id' => $merchant->getId(),
            'missing_document_requirements'         => $missingDocumentRequirements,
            'requirements_from_submitted_documents' => $documentRequirementsFromSubmittedDocuments
        ]);

        $requirements = array_merge($requirements, $missingDocumentRequirements, $documentRequirementsFromSubmittedDocuments);

        return [$requirements, $missingOptionalDocumentRequirements];
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
     * @param Detail\Entity $merchantDetails
     * @param array         $requiredDocuments
     *
     * @return array
     */
    private function getMissingDocumentRequirements(Detail\Entity $merchantDetails, array $requiredDocuments, array $optionalDocuments): array
    {
        $missingDocumentRequirements = [];

        $missingOptionalDocumentRequirements = [];

        $missingDocuments = array_merge($requiredDocuments, $optionalDocuments);

        foreach ($missingDocuments as $field => $fieldData)
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

            $requirement[Constants::STATUS] = (array_key_exists($field, $requiredDocuments) == true) ? Constants::REQUIRED : Constants::OPTIONAL;

            $requirement[Constants::REASON_CODE] = Constants::DOCUMENT_MISSING;

            if(array_key_exists($field, $requiredDocuments) == true)
            {
                $missingDocumentRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
            }
            else
            {
                $missingOptionalDocumentRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
            }
        }

        $this->trace->info(
            TraceCode::MERCHANT_DOCUMENT_REQUIREMENTS,
            [
                'missing_document_requirements' => $missingDocumentRequirements,
                'missing_optional_document_requirements' => $missingOptionalDocumentRequirements
            ]);

        return [array_values($missingDocumentRequirements), array_values($missingOptionalDocumentRequirements)];
    }

    /**
     * ------------------------------- After form submission -------------------------------
     * This function returns document required fields after L2 form submission.
     * If document is marked as NC through auto NC or by manual verification, we show it in requirements
     *
     * @param Detail\Entity $merchantDetails
     * @param array         $ncDocuments
     * @param array         $formattedNCDocumentsReasons
     *
     * @return array
     */
    private function getNCDocumentRequirements(Detail\Entity $merchantDetails, array $ncDocuments, array $formattedNCDocumentsReasons): array
    {
        $ncDocumentRequirements = [];

        $isNoDocOnboardingEnabled = $merchantDetails->merchant->isNoDocOnboardingEnabled();

        foreach ($ncDocuments as $documentType => $fieldData)
        {
            if($isNoDocOnboardingEnabled === true and in_array($documentType, Constants::NO_DOC_OPTIONAL_DOC_FIELDS) === true)
            {
                continue;
            }

            $fieldReference = Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$documentType] . '.' . $documentType;

            if (in_array($documentType, SelectiveRequiredFields::BANK_PROOF_DOCUMENTS) === true)
            {
                $fieldReference = Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$documentType];
            }

            $requirement = [Constants::FIELD_REFERENCE => $fieldReference];

            $entity = $fieldData[Constants::ENTITY];

            $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[$entity][Constants::DOCUMENT];

            if (array_key_exists($documentType, $formattedNCDocumentsReasons) === true)
            {
                $requirement[Constants::REASON_CODE] = Detail\Status::NEEDS_CLARIFICATION;

                $requirement[Constants::DESCRIPTION] = $formattedNCDocumentsReasons[$documentType][0][NeedsClarification\Constants::REASON_DESCRIPTION];

                $requirement[Constants::STATUS] = Constants::REQUIRED;

                $ncDocumentRequirements[] = $requirement;
            }
        }

        return $ncDocumentRequirements;
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
     * @param Detail\Entity   $merchantDetail
     * @param array           $fields
     *
     * @param array           $clarificationReasons
     *
     * @return array
     */
    private function getFieldRequirements(Detail\Entity $merchantDetail, array $fields, array $clarificationReasons, array $optionalFields): array
    {
        $missingFieldRequirements = [];

        $missingOptionalFieldRequirements = [];

        $needsClarificationFields = $clarificationReasons[Constants::FIELDS] ?? [];

        $stakeholderExists = (new Stakeholder\Core())->checkIfStakeholderExists($merchantDetail);

        $stakeholder = null;

        if ($stakeholderExists === true)
        {
            $stakeholder = (new Stakeholder\Core())->createOrFetchStakeholder($merchantDetail);
        }

        $allFields = array_merge($fields, $optionalFields);

        foreach ($allFields as $field => $fieldData)
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
                $requirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;

                if(array_key_exists($field, $fields) === true)
                {
                    $requirement[Constants::STATUS] = Constants::REQUIRED;

                    $missingFieldRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
                }
                else
                {
                    $requirement[Constants::STATUS] = Constants::OPTIONAL;

                    $missingOptionalFieldRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
                }
            }
            else
            {
                if($entityStr === Entity::STAKEHOLDER)
                {
                    $field = Stakeholder\Constants::MERCHANT_DETAILS_COMMON_EDITABLE_FIELDS[$field];
                }

                if (array_key_exists($field, $needsClarificationFields) === true)
                {
                    $requirement[Constants::REASON_CODE] = Constants::NEEDS_CLARIFICATION;

                    $requirement[Constants::DESCRIPTION] = $needsClarificationFields[$field][0][NeedsClarification\Constants::REASON_DESCRIPTION];

                    if($entityStr === Entity::STAKEHOLDER)
                    {
                        $field = Stakeholder\Constants::MERCHANT_DETAILS_STAKEHOLDER_MAPPING[$field];
                    }

                    if(array_key_exists($field, $fields) === true)
                    {
                        $requirement[Constants::STATUS] = Constants::REQUIRED;

                        $missingFieldRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
                    }
                    else
                    {
                        $requirement[Constants::STATUS] = Constants::OPTIONAL;

                        $missingOptionalFieldRequirements[$requirement[Constants::FIELD_REFERENCE]] = $requirement;
                    }
                }
            }
        }

        return [array_values($missingFieldRequirements), array_values($missingOptionalFieldRequirements)];
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

    protected function getFieldReference(string $fieldName, string $entity): string
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
            if (array_key_exists($field, Stakeholder\Constants::MERCHANT_DETAILS_STAKEHOLDER_MAPPING))
            {
                $entity = Entity::STAKEHOLDER;

                $field = Stakeholder\Constants::MERCHANT_DETAILS_STAKEHOLDER_MAPPING[$field];
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

    protected function updateResolutionUrl(Detail\Entity $merchantDetails, Product\Entity $merchantProduct, array $requirements): array
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

    protected function getOtpVerificationLogRequirements(&$requirements, Merchant\Entity $merchant)
    {
        if($merchant->isLinkedAccount() === true)
        {
            return;
        }
        $merchantDetail = $merchant->merchantDetail;

        $hasPendingOtpLog = $this->otpCore->hasPendingOtpLog($merchant->getMerchantId(), $merchantDetail->getContactMobile());

        if ($merchant->isNoDocOnboardingEnabled() and $hasPendingOtpLog === true)
        {
            $entityFieldMapping = FieldMapping::FIELD_MAPPING[Entity::MERCHANT_OTP_VERIFICATION_LOGS];

            foreach ($entityFieldMapping as $key => $value)
            {
                $requirement = [];

                $requirement[Constants::FIELD_REFERENCE] = $value;

                $requirement[Constants::RESOLUTION_URL] = Constants::PAYMENT_CONFIG_RESOLUTION_URL;

                $requirement[Constants::STATUS] = (in_array($key, Constants::REQUIRED_OTP_FIELDS))?Constants::REQUIRED:Constants::OPTIONAL;

                $requirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;

                array_push($requirements, $requirement);
            }
        }
    }

    /**
     * This function will add a reminder in requirement array when Instantly_activated merchant would have exhausted
     * their 15k limit, urging them to submit rest of the requirements.
     * @param Merchant\Entity $merchant
     * @param array $requirements
     */
    protected function addInstantActivationAlertInRequirementIfApplicable(Merchant\Entity $merchant,array &$requirements)
    {
        $tagResult = (new AccountV2\Core())->isInstantActivationTagEnabled($merchant->getId());

        if($tagResult === false or $merchant->getAccountStatus() !== Status::INSTANTLY_ACTIVATED)
        {
            return ;
        }

        $escalation = $this->repo->merchant_onboarding_escalations->fetchEscalationForThresholdAndMilestone(
            $merchant->getId(), 'hard_limit_ia_v2', 1500000);

        if ($escalation)
        {
            foreach ($requirements as &$requirement) {
                $requirement["description"] = Constants::INSTANT_ACTIVATION_LIMIT_BREACH_DESCRIPTION;
            }
        }
    }

    public function validateRequiredFieldsNonEmpty(Detail\Entity $merchantDetails): bool
    {
        $requiredFields = (new Detail\ValidationFields())->getRequiredFieldsForInstantActV2Apis($merchantDetails->getBusinessType());

        $this->trace->info(TraceCode::INSTANT_ACTIVATION_FIELDS_REQUIREMENTS,[
            'business_type'     => $merchantDetails->getId(),
            'required_fields'   => $requiredFields,
        ]);

        $missingFields = [];

        foreach ($requiredFields as $field)
        {
            if (empty($merchantDetails->getAttribute($field)) === true)
            {
                array_push($missingFields, $field);
            }
        }

        if (count($missingFields) > 0)
        {
            $this->trace->info(TraceCode::MISSING_FIELDS_FOR_INSTANT_ACTIVATION, [
                'merchant_id'       => $merchantDetails->getMerchantId(),
                'missing_fields'    => $missingFields,
            ]);
            return false;
        }

        return true;
    }

    public function isNonTerminalStatusApplicable(Detail\Entity $merchantDetails)
    {
        $instantActivationTag = (new AccountV2\Core())->isInstantActivationTagEnabled($merchantDetails->getId());

        if($instantActivationTag === false)
        {
            $this->trace->info(TraceCode::MERCHANT_NOT_WHITELISTED_FOR_INSTANT_ACTIVATION,[
                'merchant_id'       => $merchantDetails->getId(),
            ]);
            return false;
        }

        if ($merchantDetails->getActivationStatus() === Detail\Status::INSTANTLY_ACTIVATED)
        {
            $this->trace->info(TraceCode::MERCHANT_ALREADY_INSTANTLY_ACTIVATED,[
                'merchant_id'       => $merchantDetails->getId(),
                'activation_status' => $merchantDetails->getActivationStatus()
            ]);

            return false;
        }

        if ($this->validateRequiredFieldsNonEmpty($merchantDetails) === false)
        {
            return false;
        }

        return true;
    }

    private function isSubmerchantsPartnerExcludedFromProvidingIp($submerchantId): bool
    {
        $partnerId = $this->auth->getPartnerMerchantId() ??
                     $this->repo->merchant_access_map->fetchEntityOwnerIdsForSubmerchant($submerchantId)->first();

        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $this->app['config']->get('app.excluded_partners_from_providing_subm_ip_experiment_id')
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }
}
