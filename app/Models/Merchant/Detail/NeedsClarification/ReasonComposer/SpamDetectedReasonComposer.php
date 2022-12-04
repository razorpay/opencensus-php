<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstant;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as ClarificationConstants;

class SpamDetectedReasonComposer extends BaseClarificationReasonComposer
{
    /**
     * @var array
     */
    private $clarificationMetaData;

    public function __construct($validation, array $needsClarificationMetaData)
    {
        parent::__construct();

        $this->validation = $validation;

        $this->clarificationMetaData = $needsClarificationMetaData;
    }

    public function getClarificationReason(): array
    {
        if (empty($this->clarificationMetaData) === true)
        {
            return [];
        }

        $errorCode = $this->validation->getErrorCode();

        //
        // If Message mapping is not there then just log and continue,
        //
        if (isset($this->clarificationMetaData[ClarificationConstants::REASON_MAPPING][$errorCode]) === false)
        {
            $this->trace->info(TraceCode::BVS_ERROR_MAPPING_NOT_DEFINED, [
                'error_code' => $errorCode,
            ]);

            return [];
        }

        $fieldName  = $this->clarificationMetaData[ClarificationConstants::FIELD_NAME];
        $fieldType  = $this->clarificationMetaData[ClarificationConstants::FIELD_TYPE];
        $reasonCode = $this->clarificationMetaData[ClarificationConstants::REASON_MAPPING][$errorCode];

        return [
            Entity::CLARIFICATION_REASONS => [
                $fieldName => [[
                    Constants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                    Constants::FIELD_TYPE  => $fieldType,
                    Constants::REASON_CODE => $reasonCode,
                ]],
            ]
        ];
    }

}
