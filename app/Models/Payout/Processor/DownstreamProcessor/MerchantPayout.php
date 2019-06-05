<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Models\Transaction;
use RZP\Models\Payout\Entity;

class MerchantPayout extends Base
{
    protected function setChannel(Entity $payout)
    {
        $channel = $payout->merchant->getChannel();

        $payout->setChannel($channel);
    }

    protected function postTransactionCreationProcessing(Transaction\Entity $txn, Entity $payout)
    {
        //
        // In an on-demand payout, whatever payout amount the merchant asks for, we DO NOT create
        // a payout for that amount. Instead, we deduct some fees from that amount and create the
        // payout with the REMAINING amount. For example: If a merchant wants a payout of 100rs,
        // we create a payout of 98rs only and keep the remaining 2rs as fees.
        //
        // In case of a normal payout, we add extra fees to the actual payout amount and deduct
        // that much amount of money from the merchant's balance. For example, if a merchant wants
        // to do a payout of 100rs, we create a payout of 100rs and then deduct 102rs from his balance.
        // The 2rs extra is our fees. The reason we don't deduct from the actual payout amount here is
        // because in most cases normal payout is used to payout some money to a customer (of the merchant).
        // The customer would always expect a certain amount. (we can have customer fee bearer concept later).
        //
        // In case of on-demand, it's basically a customer fee bearer kind of concept, where in the customer
        // is the actual merchant himself. He bears the fees for the payout to his account. Hence, the payout
        // happens after deducting the razorpay fees from the actual payout amount. For this reason, we also
        // reset the payout amount here.
        //
        // In both the above cases, we need to ensure that the merchant has enough balance in his account.
        // The validation for the balance would always be payout's amount + our fees.
        //

        if ($payout->getPayoutType() === Entity::ON_DEMAND)
        {
            // Here, payout amount is the amount requested by merchant for payout and fees is
            // levied over it. Also, this fees is deducted from merchant balance. This happens for
            // merchants who do not have 'es_on_demand' feature enabled. In case of 'es_on_demand'
            // merchants, payout fees will be deducted from payout amount requested by the merchant.
            // This is done to allow a merchant to do a payout on requested amount, rather than
            // calculating fees over it and failing a transaction if merchant does not have enough balance.
            $payout->setAmount($txn->getAmount());
        }
    }
}
