<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use RZP\Exception\LogicException;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\BankDetailsVerificationStatus;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;

class BankAccountClarificationComposer extends BaseClarificationReasonComposer
{

    protected $merchantDetails;

    public function __construct(Entity $merchantDetails)
    {
        parent::__construct();

        $this->merchantDetails = $merchantDetails;
    }

    public function getClarificationReason(): array
    {
        switch ($this->merchantDetails->getBankDetailsVerificationStatus())
        {
            case BankDetailsVerificationStatus::VERIFIED:
                return [];

            case BankDetailsVerificationStatus::FAILED:
            case BankDetailsVerificationStatus::NOT_MATCHED:
            case BankDetailsVerificationStatus::INCORRECT_DETAILS:

                return [
                    $this->merchantDetails::ADDITIONAL_DETAILS =>
                        [
                            Merchant\Document\Type::CANCELLED_CHEQUE => [[
                                                                             Merchant\Constants::REASON_TYPE => Merchant\Constants::PREDEFINED_REASON_TYPE,
                                                                             Merchant\Constants::FIELD_TYPE  => Merchant\Constants::DOCUMENT,
                                                                             Merchant\Constants::REASON_CODE => NeedsClarificationReasonsList::UNABLE_TO_VALIDATE_ACC_NUMBER,
                                                                         ]],
                        ]];
            default:
                throw  new LogicException("Unhandled bank detail verification status");

        }
    }
}