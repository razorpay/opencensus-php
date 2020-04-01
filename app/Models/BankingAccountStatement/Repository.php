<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_statement';

    public function bankTransactionExists($bankTxnId, $accountNumber, $bankTxnDate, $channel, $bankTxnSrlNo)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_TRANSACTION_ID, $bankTxnId)
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::TRANSACTION_DATE, $bankTxnDate)
                    ->where(Entity::BANK_SERIAL_NUMBER, $bankTxnSrlNo)
                    ->where(Entity::CHANNEL, $channel)
                    ->orderBy(Entity::ID, 'desc')
                    ->exists();
    }

    public function findLatestByAccountNumber($accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->latest(Entity::ID)
                    ->first();
    }

    public function fetchByUtrForPayout(Payout\Entity $payout)
    {
        $query = $this->newQuery()
                      ->where(Entity::UTR, $payout->getUtr());

        return $this->fetchForPayout($query, $payout);
    }

    public function fetchByCmsRefNumForPayout(Payout\Entity $payout)
    {
        // TODO: check uniqueness logic for cms_ref_no
        // JIRA ticket: https://razorpay.atlassian.net/browse/RX-695
        $cmsRefNumber = $payout->fundTransferAttempts->first()->getCmsRefNo();

        $query = $this->newQuery()
                      ->where(Entity::BANK_TRANSACTION_ID, $cmsRefNumber);

        return $this->fetchForPayout($query, $payout);
    }

    public function fetchByUtrForReversal(Reversal\Entity $reversal)
    {
        /** @var Payout\Entity $payout */
        $payout = $reversal->entity;

        $query = $this->newQuery()
                      ->where(function($query) use ($reversal, $payout)
                        {
                            $query->where(Entity::UTR, $reversal->getUtr())
                                  ->orWhere(Entity::UTR, $payout->getUtr());
                        })
                      ->where(Entity::CREATED_AT, '>=', $payout->getCreatedAt());

        return $this->fetchForReversal($query, $reversal);
    }

    public function fetchByCmsRefNumForReversal(Reversal\Entity $reversal)
    {
        /** @var Payout\Entity $payout */
        $payout = $reversal->entity;

        $cmsRefNumber = $payout->fundTransferAttempts->first()->getCmsRefNo();

        $query = $this->newQuery()
                      ->where(Entity::BANK_TRANSACTION_ID, $cmsRefNumber)
                      ->where(Entity::CREATED_AT, '>=', $payout->getCreatedAt());

        return $this->fetchForReversal($query, $reversal);
    }

    protected function fetchForReversal($query, Reversal\Entity $reversal)
    {
        $payout = $reversal->entity;

        // reversal amount contains fee and tax but txn will
        // contain only absolute amount which will match with
        // payout amount
        $basEntities = $query->where(Entity::TYPE, Type::CREDIT)
                             ->where(Entity::AMOUNT, $payout->getAmount())
                             ->where(Entity::ACCOUNT_NUMBER, $reversal->balance->getAccountNumber())
                             ->where(Entity::CHANNEL, $reversal->getChannel())
                             ->get();

        if ($basEntities->count() > 1)
        {
            throw new LogicException(
                'Found too many bas entities for a given reference',
                ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE,
                [
                    'payout_id'         => $reversal->getId(),
                    'count'             => $basEntities->count(),
                ]);
        }

        return $basEntities;
    }

    protected function fetchForPayout($query, Payout\Entity $payout)
    {
        $basEntities = $query->where(Entity::TYPE, Type::DEBIT)
                             ->where(Entity::AMOUNT, $payout->getAmount())
                             ->where(Entity::ACCOUNT_NUMBER, $payout->balance->getAccountNumber())
                             ->where(Entity::CHANNEL, $payout->getChannel())
                             ->get();

        if ($basEntities->count() > 1)
        {
            throw new LogicException(
                'Found too many bas entities for a given reference',
                ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE,
                [
                    'payout_id'         => $payout->getId(),
                    'count'             => $basEntities->count(),
                ]);
        }

        return $basEntities;
    }
}
