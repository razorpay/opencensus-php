<?php

namespace RZP\Mail\Transaction;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Models\Payout\ViewDataSerializer;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\Repository as PayoutRepository;
use RZP\Models\FundAccount\Repository as FundAccountRepository;

class Payout extends Transaction
{
    /**
     * @var array
     */
    protected $fundAccount;

    const DATE_FORMAT = 'M d, Y (h:i A)';

    public function  __construct(string $event, array $balance, array $txn, array $source, array $merchant)
    {
        parent::__construct($event, $balance, $txn, $source, $merchant);

        $this->addFundAccountAttributes();

        $this->modifySourceAttributes();

        $this->modifyTxnAttributes();
    }

    protected function getSubject(): string
    {
        $payoutId            = $this->source['id'];
        $formattedAmount     = amount_format_IN($this->txn['amount']);
        $maskedAccountNumber = mask_except_last4($this->balance['account_number']);
        $modePrefix          = ($this->mode === Mode::TEST) ? Constants::TEST_MODE_PREFIX : '';

        switch ($this->event)
        {
            case Event::PAYOUT_PROCESSED:
                return "{$modePrefix}Your A/C ending with {$maskedAccountNumber} has been debited by INR {$formattedAmount}";

            case Event::PAYOUT_REVERSED:
                return "{$modePrefix}Payout {$payoutId} has been reversed";

            default:
                throw new LogicException("Not handled transaction mail event: {$this->event}");
        }
    }

    protected function addFundAccountAttributes()
    {
        $payout = (new PayoutRepository)->find($this->source[PayoutEntity::ID]);

        if ((empty($payout) === true) and
            (array_key_exists('fund_account_id', $this->source) === true))
        {
            $fundAccount = (new FundAccountRepository)->find($this->source['fund_account_id']);
        }
        else
        {
            $fundAccount = $payout->fundAccount;
        }

        $this->fundAccount                           = $fundAccount->toArrayPublic();
        $this->fundAccount['destination']            = $fundAccount->getAccountDestinationAsText();
        $this->fundAccount['account_type_formatted'] = $fundAccount->getAccountTypeAsText();
    }

    protected function modifySourceAttributes()
    {
        $this->source = (new ViewDataSerializer($this->source))->serializePayoutForPublic();
    }

    protected function modifyTxnAttributes()
    {
        $this->txn['created_at_formatted'] = Carbon::createFromTimestamp(
            $this->txn['created_at'] , Timezone::IST)->format(self::DATE_FORMAT);
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
