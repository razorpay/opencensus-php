<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\InternationalEnablement\Detail\Constants as IEDetailConstants;

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

    public static $externalPayloadDraftRules = [
        Constants::FIRC                                 => 'nullable|sometimes|array|max:3',
        Constants::FIRC . '.*.id'                       => 'required|string|starts_with:doc_|size:18',
        Constants::FIRC . '.*.display_name'             => 'nullable|sometimes|string|max:100',
        Constants::IE_CODE                              => 'nullable|sometimes|array|max:3',
        Constants::IE_CODE . '.*.id'                    => 'required|string|starts_with:doc_|size:18',
        Constants::IE_CODE . '.*.display_name'          => 'nullable|sometimes|string|max:100',
        Constants::INVOICES                             => 'nullable|sometimes|array|max:3',
        Constants::INVOICES . '.*.id'                   => 'required|string|starts_with:doc_|size:18',
        Constants::INVOICES . '.*.display_name'         => 'nullable|sometimes|string|max:100',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE          => 'nullable|sometimes|array|max:3',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD => 'nullable|sometimes|array|max:3',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::OTHERS => 'nullable|sometimes|array|max:10',
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

        Constants::BANK_STATEMENT_INWARD_REMITTANCE          => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD => 'required_if:accepts_intl_txns,true|array|filled|between:1,3',

        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::BANK_STATEMENT_INWARD_REMITTANCE . '.*.display_name' => 'nullable|sometimes|string|max:100',
        
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.id'           => 'required|string|starts_with:doc_|size:18',
        Constants::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . '.*.display_name' => 'nullable|sometimes|string|max:100',

        Constants::OTHERS => 'nullable|sometimes|array|max:10',
    ];

    public function validateExternalPayload(array $documentsPayload, string $action)
    {
        $this->throwValidationError = false;

        $this->validateInput('external_payload_' . $action, $documentsPayload);

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
