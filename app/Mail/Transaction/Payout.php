<?php

namespace RZP\Mail\Transaction;

class Payout extends Transaction
{
    protected function getSubject(): string
    {
        return sprintf(
            'RazorpayX | Your A/c %s has been debited by INR %s',
            $this->balance['account_number'],
            amount_format_IN($this->txn['amount']));
    }

    protected function addHtmlView()
    {
        return $this->view('emails.transaction.payout');
    }
}
