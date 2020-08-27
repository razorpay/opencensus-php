<?php

namespace RZP\Mail\Transaction;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\BankTransfer\Entity;

class BankTransfer extends Transaction
{
    const DATE_FORMAT = 'M d, Y (h:i A)';

    public function  __construct(string $event, array $balance, array $txn, array $source, array $merchant)
    {
        parent::__construct($event, $balance, $txn, $source, $merchant);

        $this->modifySourceAttributes();
    }

    protected function getSubject(): string
    {
        return sprintf(
            "Your RazorpayX A/C %s is credited with INR %s",
            substr(mask_except_last4($this->balance['account_number']), -6),
            amount_format_IN($this->txn['amount']));
    }

    protected function addHtmlView()
    {
        return $this->view('emails.transaction.bank_transfer');
    }

    protected function modifySourceAttributes()
    {
        $value = $this->source[Entity::CREATED_AT] ?? null;

        $formatted = ($value === null) ? null : Carbon::createFromTimestamp($value , Timezone::IST)->format(self::DATE_FORMAT);

        $this->source[Entity::CREATED_AT . '_formatted'] = $formatted;
    }
}
