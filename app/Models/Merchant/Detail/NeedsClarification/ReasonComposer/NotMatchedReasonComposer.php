<?php


namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as ClarificationConstants;

class NotMatchedReasonComposer extends BaseClarificationReasonComposer
{

    private $validation;

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
        $clarificationReasons = [];

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

        if ($this->validation->getOwnerType() === Constants::MERCHANT)
        {
            $merchantId = $this->validation->getOwnerId();

            $merchant = app('repo')->merchant->findOrFail($merchantId);

            if($merchant->isLinkedAccount() === true)
            {
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

        $ruleResultVerifierFactory = new RuleResultVerifier\Factory();

        $ruleResultVerifier = $ruleResultVerifierFactory->getInstance($this->validation);

        //
        // Check if required rule is matched or not.
        //
        $ruleResultForMatched = $ruleResultVerifier->verifyAndReturnRuleResultForMatched();

        $notMatchedClarificationReasons = $this->getClarificationsReasonsForNotMatched($ruleResultForMatched);

        $clarificationReasons[Entity::CLARIFICATION_REASONS] = [];

        foreach ($notMatchedClarificationReasons as $key => $value)
        {
           $clarificationReasons[Entity::CLARIFICATION_REASONS][$key] = $value;
        }

        return $clarificationReasons;
    }

    protected function getClarificationsReasonsForNotMatched(array $ruleResultForMatched): array
    {
        $clarificationReasons = [];

        if (isset($ruleResultForMatched[ClarificationConstants::IS_SIGNATORY_NAME_MATCHED]) &&
            $ruleResultForMatched[ClarificationConstants::IS_SIGNATORY_NAME_MATCHED] === false)
        {
            $clarificationReasons[Entity::PROMOTER_PAN_NAME] = [[
                Constants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                Constants::FIELD_TYPE  => ClarificationConstants::TEXT,
                Constants::REASON_CODE => NeedsClarificationReasonsList::SIGNATORY_NAME_NOT_MATCHED,]];

        }

        if (isset($ruleResultForMatched[ClarificationConstants::IS_COMPANY_NAME_MATCHED]) &&
            $ruleResultForMatched[ClarificationConstants::IS_COMPANY_NAME_MATCHED] === false)
        {
            $clarificationReasons[Entity::BUSINESS_NAME] = [[
                Constants::REASON_TYPE => Constants::PREDEFINED_REASON_TYPE,
                Constants::FIELD_TYPE  => ClarificationConstants::TEXT,
                Constants::REASON_CODE => NeedsClarificationReasonsList::COMPANY_NAME_NOT_MATCHED,]];
        }

        return $clarificationReasons;
    }
}
