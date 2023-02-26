<?php

namespace RZP\Models\CorpCard;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;
use RZP\Models\Merchant\Balance\AccountType;

class Core extends Base\Core
{
    public function onboardCapitalCorpCardForPayouts($merchantId, $request)
    {
        $balanceId = $this->repo->transaction(
            function() use ($merchantId, $request) {
                list ($balance, $isNewBalance) = $this->createBalanceForCapitalCorpCard($merchantId, $request);

                $this->trace->info(TraceCode::CORP_CARD_BALANCE_CREATE,
                                   [
                                       'balance'      => $balance->toArrayPublic(),
                                       'isNewBalance' => $isNewBalance
                                   ]
                );

                if ($isNewBalance === false)
                {
                    return $balance['id'];
                }

                $bankingAccountInput = [
                    Entity::CHANNEL        => Constants::M2P_CHANNEL,
                    Entity::ACCOUNT_NUMBER => $request[Constants::ACCOUNT_NUMBER],
                    Entity::ACCOUNT_TYPE   => AccountType::CORP_CARD,
                    Entity::STATUS         => Status::CREATED
                ];

                (new BankingAccount\Service())->createBankingAccountForCapitalCorpCard($bankingAccountInput, $balance);

                return $balance->getId();
            });

        return [
            Constants::BALANCE_ID => $balanceId,
        ];
    }

    public function createBalanceForCapitalCorpCard($merchantId, $request)
    {
        try
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $attributes = [
                Merchant\Balance\Entity::ACCOUNT_TYPE   => Merchant\Balance\AccountType::CORP_CARD,
                Merchant\Balance\Entity::CHANNEL        => Constants::M2P_CHANNEL,
                Merchant\Balance\Entity::ACCOUNT_NUMBER => $request[Constants::ACCOUNT_NUMBER],
            ];

            return $this->createBalance($merchant, $attributes);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::CORP_CARD_BALANCE_CREATE_FAIL,
                [
                    Constants::MERCHANT_ID    => $merchantId,
                    Constants::ACCOUNT_NUMBER => $request[Constants::ACCOUNT_NUMBER],
                    Constants::CHANNEL        => Constants::M2P_CHANNEL,
                    Constants::ERROR          => $e->getMessage()
                ]);

            throw $e;
        }
    }

    public function createBalance(Merchant\Entity $merchant, $attributes): array
    {
        $balance = $this->repo->balance->getBalanceByMerchantIdAccountNumberChannelAndAccountType(
            $merchant->getId(),
            $attributes[Constants::ACCOUNT_NUMBER],
            $attributes[Constants::CHANNEL],
            AccountType::CORP_CARD);

        if (empty($balance) === true)
        {
            $mode    = $this->app['rzp.mode'];
            $balance = (new Merchant\Balance\Core)->createBalanceForCorpCard($merchant, $attributes, $mode);

            return [$balance, true];
        }

        return [$balance, false];
    }
}
