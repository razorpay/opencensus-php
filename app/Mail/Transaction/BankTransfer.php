<?php

namespace RZP\Mail\Transaction;

class BankTransfer extends Transaction
{
    protected function getSubject(): string
    {
        return sprintf(
            'RazorpayX | Your A/c %s has been credited with INR %s',
            $this->balance['account_number_masked'],
            $this->txn['amount_formatted']);
    }

    protected function addHtmlView()
    {
        return $this->view('emails.transaction.bank_transfer');
    }
}
