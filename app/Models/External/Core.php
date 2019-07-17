<?php

namespace RZP\Models\External;

use RZP\Models\Base;
use RZP\Models\External;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\BankingAccountStatement as BAS;

class Core extends Base\Core
{
    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    public function create(BAS\Entity $basEntity): External\Entity
    {
        $external = $this->repo->transaction(function () use ($basEntity)
        {
            $external = $this->createExternalEntity($basEntity);

            list ($txn, $feeSplit) = (new Transaction\Processor\External($external))->createTransaction();

            $this->repo->saveOrFail($txn);

            $this->trace->info(TraceCode::EXTERNAL_SAVE, $external->toArray());

            //
            // Note that the external entity should be saved only after the transaction
            // has been saved since the transaction_id is not nullable in external.
            //
            $this->repo->saveOrFail($external);

            return $external;
        });

        return $external;
    }

    protected function createExternalEntity(BAS\Entity $basEntity): External\Entity
    {
        $input = $this->createInputForExternalEntity($basEntity);

        $this->trace->info(
            TraceCode::EXTERNAL_TXN_INPUT_DATA,
            [
                'input' => $input,
            ]);

        $external = (new Entity)->build($input);

        $utr = $basEntity->getUtrFromDescription();

        $external->setUtr($utr);

        $external->bankingAccountStatement()->associate($basEntity);

        $external->merchant()->associate($basEntity->merchant);

        $balance = $this->getBalanceUsingAccount($basEntity);

        $external->balance()->associate($balance);

        return $external;
    }

    protected function createInputForExternalEntity(BAS\Entity $basEntity): array
    {
        $input = [
            External\Entity::CHANNEL                    => $basEntity->getChannel(),
            External\Entity::BANK_REFERENCE_NUMBER      => $basEntity->getBankTransactionId(),
            External\Entity::AMOUNT                     => $basEntity->getAmount(),
            External\Entity::CURRENCY                   => $basEntity->getCurrency(),
            External\Entity::TYPE                       => $basEntity->getType(),
        ];

        return $input;
    }

    protected function getBalanceUsingAccount(BAS\Entity $basEntity): Balance\Entity
    {
        $merchantId    = $basEntity->getMerchantId();
        $accountNumber = $basEntity->getAccountNumber();
        $channel       = $basEntity->getChannel();

        $balance = $this->repo->balance->getBalanceByMerchantIdAccountNumberAndChannelOrFail($merchantId,
                                                                                             $accountNumber,
                                                                                             $channel);

        return $balance;
    }
}
