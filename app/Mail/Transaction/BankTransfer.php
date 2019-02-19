<?php

namespace RZP\Mail\Transaction;

class BankTransfer extends Transaction
{
    protected function getSubject(): string
    {
        return sprintf(
            'Your A/C ending with %s has been credited with INR %s',
            mask_except_last4($this->balance['account_number']),
            amount_format_IN($this->txn['amount']));
    }

    protected function addHtmlView()
    {
        return $this->view('emails.transaction.bank_transfer');
    }
}
