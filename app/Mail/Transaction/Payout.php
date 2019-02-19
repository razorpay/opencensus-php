<?php

namespace RZP\Mail\Transaction;

use RZP\Models\FundAccount;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\Repository as PayoutRepository;

class Payout extends Transaction
{
    /**
     * @var array
     */
    protected $fundAccount;

    public function  __construct(string $event, array $balance, array $txn, array $source, array $merchant)
    {
        parent::__construct($event, $balance, $txn, $source, $merchant);

        $this->addFundAccountAttributes();

        $this->modifySourceAttributes();
    }

    protected function getSubject(): string
    {
        $payoutId            = $this->source['id'];
        $formattedAmount     = amount_format_IN($this->txn['amount']);
        $maskedAccountNumber = mask_except_last4($this->balance['account_number']);

        switch ($this->event)
        {
            case Event::PAYOUT_PROCESSED:
                return "Your A/C ending with {$maskedAccountNumber} has been debited by INR {$formattedAmount}";

            case Event::PAYOUT_REVERSED:
                return "Payout {$payoutId} has been reversed";

            default:
                throw new LogicException("Not handled transaction mail event: {$this->event}");
        }
    }

    protected function addFundAccountAttributes()
    {
        $payout = (new PayoutRepository)->find($this->source[PayoutEntity::ID]);
        $fundAccount = $payout->fundAccount;

        $this->fundAccount = $fundAccount->toArrayPublic();
        $this->fundAccount['destination'] = $fundAccount->getAccountDestinationAsText();
        $this->fundAccount['account_type_formatted'] = $fundAccount->getAccountTypeAsText();
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
        // E.g. payout_processed, payout_reversed etc.
        $view = str_replace('.', '_', $this->event);

        return $this->view("emails.transaction.{$view}");
    }
}
