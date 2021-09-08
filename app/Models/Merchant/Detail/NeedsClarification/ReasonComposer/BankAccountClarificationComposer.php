<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use RZP\Exception\LogicException;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\BankDetailsVerificationStatus;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as NCConstants;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Trace\TraceCode;

class BankAccountClarificationComposer extends BaseClarificationReasonComposer
{

    protected $merchantDetails;

    /**
     * @var array
     */
    private $clarificationMetaData;
    public function __construct(Entity $merchantDetails,array $needsClarificationMetaData)
    {
        parent::__construct();

        $this->merchantDetails = $merchantDetails;

        $this->clarificationMetaData = $needsClarificationMetaData;
    }

    public function getClarificationReason(): array
    {
        if (empty($this->clarificationMetaData) === true)
        {
            return [];
        }
        switch ($this->merchantDetails->getBankDetailsVerificationStatus())
        {
            case BankDetailsVerificationStatus::NOT_MATCHED:
            case BankDetailsVerificationStatus::VERIFIED:
                return [];

            case BankDetailsVerificationStatus::FAILED:
            case BankDetailsVerificationStatus::INCORRECT_DETAILS:

                $response=[];
                foreach ($this->clarificationMetaData[NCConstants::ADDITIONAL_DETAILS][NCConstants::FIELDS] as $field) {
                    $response[$field[NCConstants::FIELD_NAME]]=[[
                        NCConstants::REASON_TYPE=>Merchant\Constants::PREDEFINED_REASON_TYPE,
                        NCConstants::FIELD_TYPE=>$field[NCConstants::FIELD_TYPE],
                        NCConstants::FIELD_VALUE=>$this->merchantDetails->getAttribute($field[NCConstants::FIELD_NAME]),
                        NCConstants::REASON_CODE=>NeedsClarificationMetaData::BUSINESS_TYPE_REASON_CODE_MAPPING[$this->merchantDetails->getBusinessType()]
                    ]];
                }

                return [
                    $this->merchantDetails::ADDITIONAL_DETAILS => $response
                ];

            default:
                throw  new LogicException("Unhandled bank detail verification status");

        }
    }
}
