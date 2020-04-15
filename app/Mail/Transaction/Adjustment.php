<?php

namespace RZP\Mail\Transaction;

use RZP\Constants\Mode;
use RZP\Mail\Base\Constants;
use RZP\Models\Adjustment\ViewDataSerializer;

class Adjustment extends Transaction
{
    public function  __construct(string $event, array $balance, array $txn, array $source, array $merchant)
    {
        parent::__construct($event, $balance, $txn, $source, $merchant);

        $this->modifySourceAttributes();
    }

    protected function getSubject(): string
    {
        $formattedAmount     = amount_format_IN(abs($this->txn['amount']));
        $maskedAccountNumber = mask_except_last4($this->balance['account_number']);
        $modePrefix          = ($this->mode === Mode::TEST) ? Constants::TEST_MODE_PREFIX : '';

        if ($this->source['amount'] >0 )
        {
            return "{$modePrefix}Your A/C ending with {$maskedAccountNumber} has been credited by INR {$formattedAmount}";
        }
        else
        {
            return "{$modePrefix}Your A/C ending with {$maskedAccountNumber} has been debited by INR {$formattedAmount}";
        }
    }

    protected function modifySourceAttributes()
    {
        $this->source = (new ViewDataSerializer($this->source))->serializeAdjustmentForPublic();
    }

    protected function addHtmlView()
    {
        return $this->view("emails.transaction.adjustment");
    }
}
