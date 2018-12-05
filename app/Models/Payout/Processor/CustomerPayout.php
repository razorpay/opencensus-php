<?php

namespace RZP\Models\Payout\Processor;

use RZP\Exception;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

class CustomerPayout extends Base
{
    protected function setChannel()
    {
        $this->channel = Settlement\Channel::YESBANK;
    }

    protected function createTxns(Payout\Entity $payout)
    {
        $txnCore = new Transaction\Core;

        $txn = $txnCore->createFromPayout($payout);

        $payout->setFees($txn->getFee());
        $payout->setTax($txn->getTax());

        $this->validateMerchantBalance($payout);

        $txnCore->updateBalances($txn, true);

        $this->repo->saveOrFail($txn);
    }

    protected function validateMerchantBalance(Payout\Entity $payout)
    {
        $debitAmount = $payout->getAmount() + $payout->getFees();

        $hasBalance = (new Merchant\Balance\Core)->checkMerchantBalance($payout->merchant, $debitAmount);

        if ($hasBalance === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE);
        }
    }
}
