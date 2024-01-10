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
        Constants::FIRC . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::FIRC . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::MSME_CERTIFICATE                                     => 'nullable|sometimes|array|max:3',
        Constants::MSME_CERTIFICATE . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::MSME_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME    => 'nullable|sometimes|string|max:100',

        Constants::SHOP_ESTABLISHMENT_CERTIFICATE                                     => 'nullable|sometimes|array|max:3',
        Constants::SHOP_ESTABLISHMENT_CERTIFICATE . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::SHOP_ESTABLISHMENT_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME    => 'nullable|sometimes|string|max:100',

        Constants::TRADE_LICENSE                                     => 'nullable|sometimes|array|max:3',
        Constants::TRADE_LICENSE . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::TRADE_LICENSE . '.*.'. Constants::DISPLAY_NAME    => 'nullable|sometimes|string|max:100',

        Constants::PROOF_OF_PROFESSION                                     => 'nullable|sometimes|array|max:3',
        Constants::PROOF_OF_PROFESSION . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::PROOF_OF_PROFESSION . '.*.'. Constants::DISPLAY_NAME    => 'nullable|sometimes|string|max:100',

        Constants::SALES_TAX_RETURNS                                    => 'nullable|sometimes|array|max:3',
        Constants::SALES_TAX_RETURNS . '.*.'.Constants::ID              => 'required|string|starts_with:doc_|size:18',
        Constants::SALES_TAX_RETURNS . '.*.'. Constants::DISPLAY_NAME   => 'nullable|sometimes|string|max:100',

        Constants::INCOME_TAX_RETURNS                                   => 'nullable|sometimes|array|max:3',
        Constants::INCOME_TAX_RETURNS . '.*.'.Constants::ID             => 'required|string|starts_with:doc_|size:18',
        Constants::INCOME_TAX_RETURNS . '.*.'. Constants::DISPLAY_NAME  => 'nullable|sometimes|string|max:100',

        Constants::GST_CERTIFICATE                                      => 'nullable|sometimes|array|max:3',
        Constants::GST_CERTIFICATE . '.*.'.Constants::ID                => 'required|string|starts_with:doc_|size:18',
        Constants::GST_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::CERTIFICATION_REGISTRATION_BY_TAX_AUTH                                   => 'nullable|sometimes|array|max:3',
        Constants::CERTIFICATION_REGISTRATION_BY_TAX_AUTH . '.*.'.Constants::ID             => 'required|string|starts_with:doc_|size:18',
        Constants::CERTIFICATION_REGISTRATION_BY_TAX_AUTH . '.*.'. Constants::DISPLAY_NAME  => 'nullable|sometimes|string|max:100',

        Constants::IEC_LICENSE                                      => 'nullable|sometimes|array|max:3',
        Constants::IEC_LICENSE . '.*.'.Constants::ID                => 'required|string|starts_with:doc_|size:18',
        Constants::IEC_LICENSE . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::UTILITY_BILLS                                    => 'nullable|sometimes|array|max:3',
        Constants::UTILITY_BILLS . '.*.'.Constants::ID              => 'required|string|starts_with:doc_|size:18',
        Constants::UTILITY_BILLS . '.*.'. Constants::DISPLAY_NAME   => 'nullable|sometimes|string|max:100',

        Constants::MOA                                      => 'nullable|sometimes|array|max:3',
        Constants::MOA . '.*.'.Constants::ID                => 'required|string|starts_with:doc_|size:18',
        Constants::MOA . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::AOA                                      => 'nullable|sometimes|array|max:3',
        Constants::AOA . '.*.'.Constants::ID                => 'required|string|starts_with:doc_|size:18',
        Constants::AOA . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::DARPAN_PORTAL                                    => 'nullable|sometimes|array|max:3',
        Constants::DARPAN_PORTAL . '.*.'.Constants::ID              => 'required|string|starts_with:doc_|size:18',
        Constants::DARPAN_PORTAL . '.*.'. Constants::DISPLAY_NAME   => 'nullable|sometimes|string|max:100',

        Constants::UBO                                    => 'nullable|sometimes|array|max:3',
        Constants::UBO . '.*.'.Constants::ID              => 'required|string|starts_with:doc_|size:18',
        Constants::UBO . '.*.'. Constants::DISPLAY_NAME   => 'nullable|sometimes|string|max:100',

        Constants::IE_CODE                                           => 'nullable|sometimes|array|max:3',
        Constants::IE_CODE . '.*.'.Constants::ID                     => 'required|string|starts_with:doc_|size:18',
        Constants::IE_CODE . '.*.'. Constants::DISPLAY_NAME          => 'nullable|sometimes|string|max:100',
        Constants::INVOICES                                         => 'nullable|sometimes|array|max:3',
        Constants::INVOICES . '.*.'.Constants::ID                   => 'required|string|starts_with:doc_|size:18',
        Constants::INVOICES . '.*.'. Constants::DISPLAY_NAME        => 'nullable|sometimes|string|max:100',

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

        Constants::IATA . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::IATA . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::FCRA . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::FCRA . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::FSSAI . '.*.'.Constants::ID                      => 'required|string|starts_with:doc_|size:18',
        Constants::FSSAI . '.*.'. Constants::DISPLAY_NAME            => 'nullable|sometimes|string|max:100',

        Constants::NBFC . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::NBFC . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::AMFI . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::AMFI . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::TRAI . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::TRAI . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::RERA . '.*.'.Constants::ID                       => 'required|string|starts_with:doc_|size:18',
        Constants::RERA . '.*.'. Constants::DISPLAY_NAME             => 'nullable|sometimes|string|max:100',

        Constants::HALLMARK_916_BIS . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_916_BIS . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',

        Constants::HALLMARK_925 . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_925 . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::GII . '.*.'.Constants::ID                        => 'required|string|starts_with:doc_|size:18',
        Constants::GII . '.*.'. Constants::DISPLAY_NAME              => 'nullable|sometimes|string|max:100',

        Constants::SEBI_CERTIFICATE . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::SEBI_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',

        Constants::FEMA_FFMA_CERTIFICATE . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::FEMA_FFMA_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',

        Constants::AYUSH_CERTIFICATE . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::AYUSH_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.'.Constants::ID            => 'required|string|starts_with:doc_|size:18',
        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME  => 'nullable|sometimes|string|max:100',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE          => 'nullable|sometimes|array|max:3',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD => 'nullable|sometimes|array|max:3',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',

        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',

        Constants::OTHERS => 'nullable|sometimes|array|max:10',
    ];

    public static $externalPayloadIataRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::IATA                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::IATA . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::IATA . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadFcraRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::FCRA                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::FCRA . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::FCRA . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadFssaiRules = [
        'accepts_intl_txns'                  => 'required|boolean',
        Constants::FSSAI                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::FSSAI . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::FSSAI . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadNbfcRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::NBFC                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::NBFC . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::NBFC . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadAyushCertificateRules = [
        'accepts_intl_txns'                              => 'required|boolean',
        Constants::AYUSH_CERTIFICATE                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::AYUSH_CERTIFICATE . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::AYUSH_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadSebiCertificateRules = [
        'accepts_intl_txns'                             => 'required|boolean',
        Constants::SEBI_CERTIFICATE                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::SEBI_CERTIFICATE . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::SEBI_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadFemaFfmaCertificateRules = [
        'accepts_intl_txns'                                  => 'required|boolean',
        Constants::FEMA_FFMA_CERTIFICATE                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::FEMA_FFMA_CERTIFICATE . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::FEMA_FFMA_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadAmfiRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::AMFI                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::AMFI . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::AMFI . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadTraiRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::TRAI                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::TRAI . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::TRAI . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadReraRules = [
        'accepts_intl_txns'                 => 'required|boolean',
        Constants::RERA                     => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::RERA . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::RERA . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadGamingAddendumCertificateRules = [
        'accepts_intl_txns'                                         => 'required|boolean',
        Constants::GAMING_ADDENDUM_CERTIFICATE                      => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.'.Constants::ID            => 'required|string|starts_with:doc_|size:18',
        Constants::GAMING_ADDENDUM_CERTIFICATE . '.*.'. Constants::DISPLAY_NAME  => 'nullable|sometimes|string|max:100',
    ];

    public static $externalPayloadHallmarkGiiRules = [
        'accepts_intl_txns'                             => 'required|boolean',
        Constants::HALLMARK_916_BIS                     => 'nullable|sometimes|array|max:3',
        Constants::HALLMARK_916_BIS . '.*.'.Constants::ID           => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_916_BIS . '.*.'. Constants::DISPLAY_NAME => 'nullable|sometimes|string|max:100',

        Constants::HALLMARK_925                         => 'nullable|sometimes|array|max:3',
        Constants::HALLMARK_925 . '.*.'.Constants::ID               => 'required|string|starts_with:doc_|size:18',
        Constants::HALLMARK_925 . '.*.'. Constants::DISPLAY_NAME     => 'nullable|sometimes|string|max:100',

        Constants::GII                                  => 'nullable|sometimes|array|max:3',
        Constants::GII . '.*.'.Constants::ID                        => 'required|string|starts_with:doc_|size:18',
        Constants::GII . '.*.'. Constants::DISPLAY_NAME              => 'nullable|sometimes|string|max:100',
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
