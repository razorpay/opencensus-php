<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Transaction;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class Base extends BaseCore
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $payout->setStatus(Status::CREATED);

        $this->createTransaction($payout);

        $this->createFundTransferAttempt($payout, $ftaAccount);
    }

    public function processTransaction(Entity $payout)
    {
        $this->createTransaction($payout);
    }

    protected function createTransaction(Entity $payout)
    {
        list ($txn, $feeSplit) = (new Transaction\Processor\Payout($payout))->createTransaction();

        $payout->setFees($txn->getFee());
        $payout->setTax($txn->getTax());

        $this->repo->saveOrFail($txn);

        (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

        $this->repo->saveOrFail($txn);

        $this->postTransactionCreationProcessing($txn, $payout);
    }

    protected function createFundTransferAttempt(Entity $payout, $ftaAccount)
    {
        $ftaInput = [
            FundTransferAttempt\Entity::PURPOSE   => $payout->getPurposeType(),
            FundTransferAttempt\Entity::CHANNEL   => $payout->getChannel(),
            FundTransferAttempt\Entity::MODE      => $payout->getMode(),
            FundTransferAttempt\Entity::NARRATION => $payout->getNarration(),
        ];

        $ftaCore = new FundTransferAttempt\Core;

        $ftaAccountEntity = $ftaAccount->getEntity();

        switch ($ftaAccountEntity)
        {
            case Constants\Entity::BANK_ACCOUNT:
                $ftaCore->createWithBankAccount($payout, $ftaAccount, $ftaInput);
                break;

            case Constants\Entity::VPA:
                $ftaCore->createWithVpa($payout, $ftaAccount, $ftaInput);
                break;

            case Constants\Entity::CARD:
                $ftaCore->createWithCard($payout, $ftaAccount, $ftaInput);
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'Payout fta destination entity is invalid. '. $ftaAccount->getEntity(),
                    [
                        'payout_id'             => $payout->getId(),
                        'fta_account_id'        => $ftaAccount->getId(),
                        'fta_account_entity'    => $ftaAccountEntity,
                    ]);
        }
    }

    protected function postTransactionCreationProcessing(Transaction\Entity $txn, Entity $payout)
    {
        return;
    }
}
