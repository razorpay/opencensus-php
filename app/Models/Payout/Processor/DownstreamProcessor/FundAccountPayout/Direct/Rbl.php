<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Direct;

use RZP\Models\Payout;

class Rbl extends Base
{
    protected function getMerchantBalanceToCheckForQueued(Payout\Entity $payout)
    {
        // In case of current accounts(direct), balance in balance entity is stale since in our system we create
        // transactions only when we fetch account statement from bank.So for current account we can't use balance
        // from balance table.
        // So before making payout we need to get balance amount in merchant's account from gateway which is then stored
        // in banking account table in our system .
        // We fetch balance from gateway if balance last fetched at was a while ago(using threshold to decide that).
        // We then use this balance amount to create payout or queue it if low balance.

        $merchantBankingAccount = (new Payout\Core)->fetchAndUpdateGatewayBalanceIfStale($payout->balance);

        $merchantBalance = $payout->balance->getBalance();

        if ($merchantBankingAccount->isGatewayBalanceFetchCronMoreUpdated() === true)
        {
            $merchantBalance = $merchantBankingAccount->getGatewayBalance();
        }

        return $merchantBalance;
    }
}
