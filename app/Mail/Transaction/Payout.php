<?php

namespace RZP\Mail\Transaction;

use RZP\Models\FundAccount;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\Repository as PayoutRepository;

class Payout extends Transaction
{
    /**
     * @var array
     */
    protected $fundAccount;

    public function  __construct(array $balance, array $txn, array $source, array $merchant)
    {
        parent::__construct($balance, $txn, $source, $merchant);

        $this->addFundAccountAttributes();

        $this->modifySourceAttributes();
    }

    protected function getSubject(): string
    {
        return sprintf(
            'RazorpayX | Your A/c %s has been debited by INR %s',
            $this->balance['account_number_masked'],
            $this->txn['amount_formatted']);
    }

    protected function addFundAccountAttributes()
    {
        // Gets related fund account attributes.
        $payout = (new PayoutRepository)->find($this->source[PayoutEntity::ID]);
        $this->fundAccount = $payout->fundAccount->toArrayPublic();

        // Fund account could be of various types. Mail body is uniform and hence we pass general attributs.
        $accountType = $this->fundAccount[FundAccount\Entity::ACCOUNT_TYPE];

        if ($accountType === FundAccount\Type::BANK_ACCOUNT)
        {
            $accountTypeFormatted = ucfirst(str_replace('_', ' ', $accountType));
            $destination = mask_except_last4($this->fundAccount[FundAccount\Entity::DETAILS]['account_number']);
        }
        elseif ($accountType === FundAccount\Type::VPA)
        {
            $accountTypeFormatted = strtoupper($accountType);
            $destination = $this->fundAccount[FundAccount\Entity::DETAILS]['address'];
        }

        $this->fundAccount['account_type_formatted'] = $accountTypeFormatted;
        $this->fundAccount['destination'] = $destination;
    }

    protected function modifySourceAttributes()
    {
        $this->source[PayoutEntity::ID] = PayoutEntity::getSignedId($this->source[PayoutEntity::ID]);
    }

    protected function addMailData()
    {
        parent::addMailData();

        return $this->with('fundAccount', $this->fundAccount);
    }

    protected function addHtmlView()
    {
        return $this->view('emails.transaction.payout');
    }
}
