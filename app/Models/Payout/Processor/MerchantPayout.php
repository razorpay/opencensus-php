<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;

class MerchantPayout extends Base
{
    const DEFAULT_MERCHANT_PAYOUT_CHANNEL = Settlement\Channel::YESBANK;

    protected function fetchAndAssociatePayoutAccount(Payout\Entity $payout, array $input)
    {
        $destination = $this->merchant->bankAccount;

        //
        // On test mode, merchant->bankAccount gets created on signup.
        // On live, it is created during activation. Hence, there should be no case where
        // the on demand payout is called and the bankAccount does not exist.
        //
        if ($destination === null)
        {
            throw new LogicException(
                'Merchant bank account should exist for on demand payouts',
                null,
                [
                    'payout_id' => $payout->getId(),
                    'input'     => $input,
                ]);
        }

        $payout->destination()->associate($destination);

        $this->fundTransferDestination = $destination;
    }

    public function calculateFees(array $input)
    {
        $this->setPayoutBalance($input);

        /** @var PublicCollection $feesSplit */
        $feesSplit = $this->repo->beginTransactionAndRollback(function () use ($input)
        {
            $payout = $this->createPayoutEntity($input);

            list($totalFee, $taxFee, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

            return $feesSplit;
        });

        $feesSplit->loadRelationWithForeignKey(Transaction\FeeBreakup\Entity::PRICING_RULE, Transaction\FeeBreakup\Entity::PRICING_RULE_ID);

        return $feesSplit->toArrayPublic();
    }

    protected function fireEventForPayoutStatus(Payout\Entity $payout)
    {
        // TODO: Should deprecate api.payout.created webhook soon!
        // https://razorpay.atlassian.net/browse/RX-854
        $this->app->events->dispatch('api.payout.created', [$payout]);
        $this->app->events->dispatch('api.payout.initiated', [$payout]);
    }
}
