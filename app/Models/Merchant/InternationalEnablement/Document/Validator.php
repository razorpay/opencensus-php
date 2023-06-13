<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\InternationalEnablement\Detail\Constants as IEDetailConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;

class Validator extends Base\Validator
{
    private $allErrors = [];

    private $lastRunErrors = [];

    private $throwValidationError = true;

    public static $createRules = [
        Entity::DOCUMENT_ID  => 'required|string|starts_with:doc_|size:18',
        Entity::TYPE         => 'required|string|in:' . Constants::DOCUMENT_TYPE_VALIDATOR_CSV,
        Entity::CUSTOM_TYPE  => 'required_if:type,others|string|min:1|max:50',
        Entity::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadDocRules = [
        Entity::ID           => 'required|string|starts_with:doc_|size:18',
        Entity::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadSubmitRules = [
        'accepts_intl_txns'                             => 'required|boolean',
        Constants::FIRC                                 => 'nullable|sometimes|array|max:3',
        Constants::FIRC . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::FIRC . '.*.display_name'             => 'nullable|sometimes|string|max:100',
        Constants::IE_CODE                              => 'nullable|sometimes|array|max:3',
        Constants::IE_CODE . '.*.id'                    => 'required|string|starts_with:doc_|size:18',
        Constants::IE_CODE . '.*.display_name'          => 'nullable|sometimes|string|max:100',
        Constants::INVOICES                             => 'nullable|sometimes|array|max:3',
        Constants::INVOICES . '.*.id'                   => 'required|string|starts_with:doc_|size:18',
        Constants::INVOICES . '.*.display_name'         => 'nullable|sometimes|string|max:100',

        Constants::IATA                                 => 'nullable|sometimes|array|max:3',
        Constants::FCRA                                 => 'nullable|sometimes|array|max:3',
        Constants::FSSAI                                => 'nullable|sometimes|array|max:3',
        Constants::NBFC                                 => 'nullable|sometimes|array|max:3',
        Constants::AMFI                                 => 'nullable|sometimes|array|max:3',
        Constants::TRAI                                 => 'nullable|sometimes|array|max:3',
        Constants::RERA                                 => 'nullable|sometimes|array|max:3',
        Constants::GII                                  => 'nullable|sometimes|array|max:3',
        Constants::HALLMARK_916_BIS                     => 'nullable|sometimes|array|max:3',
        Constants::HALLMARK_925                         => 'nullable|sometimes|array|max:3',
        Constants::SEBI_CERTIFICATE                     => 'nullable|sometimes|array|max:3',
        Constants::FEMA_FFMA_CERTIFICATE                => 'nullable|sometimes|array|max:3',
        Constants::AYUSH_CERTIFICATE                    => 'nullable|sometimes|array|max:3',
        Constants::GAMING_ADDENDUM_CERTIFICATE          => 'nullable|sometimes|array|max:3',

        Constants::IATA . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::IATA . '.*.display_name'             => 'nullable|sometimes|string|max:100',

        Constants::FCRA . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::FCRA . '.*.display_name'             => 'nullable|sometimes|string|max:100',

        Constants::FSSAI . '.*.id'                      => 'required|string|starts_with:doc_|size:18',
        Constants::FSSAI . '.*.display_name'            => 'nullable|sometimes|string|max:100',

        Constants::NBFC . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::NBFC . '.*.display_name'             => 'nullable|sometimes|string|max:100',

        Constants::AMFI . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::AMFI . '.*.display_name'             => 'nullable|sometimes|string|max:100',

        Constants::TRAI . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::TRAI . '.*.display_name'             => 'nullable|sometimes|string|max:100',

        Constants::RERA . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::RERA . '.*.display_name'             => 'nullable|sometimes|string|max:100',

        Constants::HALLMARK_916_BIS . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_916_BIS . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::HALLMARK_925 . '.*.id'               => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_925 . '.*.display_name'     => 'nullable|sometimes|string|max:100',

        Constants::GII . '.*.id'                        => 'required|string|starts_with:doc_|size:18',
        Constants::GII . '.*.display_name'              => 'nullable|sometimes|string|max:100',

        Constants::SEBI_CERTIFICATE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::SEBI_CERTIFICATE . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::FEMA_FFMA_CERTIFICATE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::FEMA_FFMA_CERTIFICATE . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::AYUSH_CERTIFICATE . '.*.id'               => 'required|string|starts_with:doc_|size:18',
        Constants::AYUSH_CERTIFICATE . '.*.display_name'     => 'nullable|sometimes|string|max:100',

        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.id'            => 'required|string|starts_with:doc_|size:18',
        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.display_name'  => 'nullable|sometimes|string|max:100',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE          => 'nullable|sometimes|array|max:3',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD => 'nullable|sometimes|array|max:3',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::OTHERS => 'nullable|sometimes|array|max:10',
    ];

    public static $externalPayloadIataRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::IATA                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::IATA . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::IATA . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadFcraRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::FCRA                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::FCRA . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::FCRA . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadFssaiRules = [
        'accepts_intl_txns'                  => 'required|boolean',
        Constants::FSSAI                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::FSSAI . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::FSSAI . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadNbfcRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::NBFC                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::NBFC . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::NBFC . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadAyushCertificateRules = [
        'accepts_intl_txns'                              => 'required|boolean',
        Constants::AYUSH_CERTIFICATE                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::AYUSH_CERTIFICATE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::AYUSH_CERTIFICATE . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadSebiCertificateRules = [
        'accepts_intl_txns'                             => 'required|boolean',
        Constants::SEBI_CERTIFICATE                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::SEBI_CERTIFICATE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::SEBI_CERTIFICATE . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadFemaFfmaCertificateRules = [
        'accepts_intl_txns'                                  => 'required|boolean',
        Constants::FEMA_FFMA_CERTIFICATE                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::FEMA_FFMA_CERTIFICATE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::FEMA_FFMA_CERTIFICATE . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadAmfiRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::AMFI                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::AMFI . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::AMFI . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadTraiRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::TRAI                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::TRAI . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::TRAI . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadReraRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::RERA                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::RERA . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::RERA . '.*.display_name' => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadGamingAddendumCertificateRules = [
        'accepts_intl_txns'                                         => 'required|boolean',
        Constants::GAMING_ADDENDUM_CERTIFICATE                      => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.id'            => 'required|string|starts_with:doc_|size:18',
        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.display_name'  => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadHallmarkGiiRules = [
        'accepts_intl_txns'                             => 'required|boolean',
        Constants::HALLMARK_916_BIS                     => 'nullable|sometimes|array|max:3',
        Constants::HALLMARK_916_BIS . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_916_BIS . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::HALLMARK_925                         => 'nullable|sometimes|array|max:3',
        Constants::HALLMARK_925 . '.*.id'               => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_925 . '.*.display_name'     => 'nullable|sometimes|string|max:100',

        Constants::GII                                  => 'nullable|sometimes|array|max:3',
        Constants::GII . '.*.id'                        => 'required|string|starts_with:doc_|size:18',
        Constants::GII . '.*.display_name'              => 'nullable|sometimes|string|max:100',
    ];

    public function getDocumentsPayloadForBusinessCategorySubCategory(string $documentType, array $documentsPayload): array
    {
        $documentsPayloadBusinessCategorySubCategory = [];

        if (array_key_exists(IEDetailConstants::ACCEPTS_INTL_TXNS, $documentsPayload) === true)
        {
            $documentsPayloadBusinessCategorySubCategory[IEDetailConstants::ACCEPTS_INTL_TXNS] = $documentsPayload[IEDetailConstants::ACCEPTS_INTL_TXNS];
        }

        if ($documentType === Constants::HALLMARK_GII)
        {
            $hallmarkGiiDocumentsTypes = Constants::HALLMARK_GII_DOCUMENTS_TYPES;

            foreach ($hallmarkGiiDocumentsTypes as $hallmarkGiiDocumentType)
            {
                if (array_key_exists($hallmarkGiiDocumentType, $documentsPayload) === true)
                {
                    $documentsPayloadBusinessCategorySubCategory[$hallmarkGiiDocumentType] = $documentsPayload[$hallmarkGiiDocumentType];
                }
            }
        }
        else
        {
            if (array_key_exists($documentType, $documentsPayload) === true)
            {
                $documentsPayloadBusinessCategorySubCategory[$documentType] = $documentsPayload[$documentType];
            }
        }

        return $documentsPayloadBusinessCategorySubCategory;
    }

    public function validateDocumentsRulesBasedOnBusinessCategoryAndSubCategory(array $documentsPayload, MerchantDetailEntity $merchantDetail)
    {
        $businessCategory  = $merchantDetail->getBusinessCategory();

        $businessSubCategory = $merchantDetail->getBusinessSubcategory();

        if ((array_key_exists($businessCategory, Constants::BUSINESS_CATEGORY_SUBCATEGORY_DOCUMENT_TYPE_MAP) === true) and
            (array_key_exists($businessSubCategory, Constants::BUSINESS_CATEGORY_SUBCATEGORY_DOCUMENT_TYPE_MAP[$businessCategory]) === true))
        {
            $documentType = Constants::BUSINESS_CATEGORY_SUBCATEGORY_DOCUMENT_TYPE_MAP[$businessCategory][$businessSubCategory];

            $documentsPayloadForBusinessCategorySubCategory = $this->getDocumentsPayloadForBusinessCategorySubCategory($documentType, $documentsPayload);

            $this->validateInput('external_payload_' . $documentType, $documentsPayloadForBusinessCategorySubCategory);
        }
    }

    public function validateDocumentsExternalPayload(array $documentsPayload, string $action, MerchantDetailEntity $merchantDetail, $version = 'v1')
    {
        if ($action === IEDetailConstants::ACTION_SUBMIT)
        {
            $this->validateInput('external_payload_' . $action, $documentsPayload);
        }

        if (($version === 'v2') and
            ($action === IEDetailConstants::ACTION_SUBMIT))
        {
            $this->validateDocumentsRulesBasedOnBusinessCategoryAndSubCategory($documentsPayload, $merchantDetail);
        }
    }

    public function validateExternalPayload(array $documentsPayload, string $action, MerchantDetailEntity $merchantDetail, $version = 'v1')
    {
        $this->throwValidationError = false;

        $this->validateDocumentsExternalPayload($documentsPayload, $action, $merchantDetail, $version);

        $this->allErrors = $this->lastRunErrors;

        $this->lastRunErrors = [];

        if (array_key_exists(Constants::OTHERS, $this->allErrors) === true)
        {
            $this->throwValidationErrorIfApplicable();
        }

        $customDocumentsPayload = $documentsPayload[Constants::OTHERS] ?? [];

        foreach ($customDocumentsPayload as $docType => $docArr)
        {
            $customDocTypeLength = strlen($docType);

            if ($customDocTypeLength == 0 || $customDocTypeLength > Entity::TYPE_FIELD_MAX_LENGTH)
            {
                $this->allErrors[Constants::OTHERS][$docType][] = 'Invalid custom type length. Should be between (1, 50)';

                continue;
            }

            if (is_null($docArr) === true)
            {
                continue;
            }

            if (is_sequential_array($docArr) === false)
            {
                $this->allErrors[Constants::OTHERS][$docType][] = 'Invalid documents structure';

                continue;
            }

            foreach ($docArr as $docIdx => $docItem)
            {
                if (is_array($docItem) === false)
                {
                    $this->allErrors[Constants::OTHERS][$docType][] = 'Invalid documents structure';

                    continue 2;
                }

                $this->validateInput('external_payload_doc', $docItem);

                if (empty($this->lastRunErrors) === false)
                {
                    $this->allErrors[Constants::OTHERS][$docType][$docIdx] = $this->lastRunErrors;

                    $this->lastRunErrors = [];
                }
            }

            if (count($docArr) > Constants::MAX_DOCUMENTS_PER_TYPE)
            {
                $this->allErrors[Constants::OTHERS][$docType][] = sprintf(
                    'Exceeded Max Number of Supported Documents per Type - %d',
                    Constants::MAX_DOCUMENTS_PER_TYPE);
            }
        }

        $this->throwValidationErrorIfApplicable();
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        if ($this->throwValidationError === false)
        {
            $this->lastRunErrors = $messages->toArray();

            return;
        }

        $errors = $messages->toArray();

        $docErrors = [
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
            'documents'           => $errors,
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
            null,
            $docErrors);
    }

    protected function throwValidationErrorIfApplicable()
    {
        if(empty($this->allErrors) === true)
        {
            return;
        }

        $docErrors = [
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
            'documents'           => $this->allErrors,
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
            null,
            $docErrors);
    }
}
