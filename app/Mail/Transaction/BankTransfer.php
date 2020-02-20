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
        $modePrefix = ($this->mode === Mode::TEST) ? Constants::TEST_MODE_PREFIX : '';

        return sprintf(
            "{$modePrefix}Your A/C ending with %s has been credited with INR %s",
            mask_except_last4($this->balance['account_number']),
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
