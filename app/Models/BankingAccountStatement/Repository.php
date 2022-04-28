<?php

namespace RZP\Models\BankingAccountStatement;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\SlackNotification;

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

    public function findLatestByAccountNumberAndChannel($accountNumber, $channel)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->latest(Entity::ID)
                    ->first();
     }

    public function findExistingStatementRecordsForBank(array $records)
    {
        $columns = [];

        $columns[] = $this->dbColumn(Entity::CHANNEL);

        $columns[]  = $this->dbColumn(Entity::ACCOUNT_NUMBER);

        $columns[] = $this->dbColumn(Entity::BANK_TRANSACTION_ID);

        $columns[] = $this->dbColumn(Entity::AMOUNT);

        $columns[] = $this->dbColumn(Entity::CURRENCY);

        $columns[] = $this->dbColumn(Entity::TYPE);

        $columns[] = $this->dbColumn(Entity::DESCRIPTION);

        $columns[] = $this->dbColumn(Entity::CATEGORY);

        $columns[] = $this->dbColumn(Entity::BANK_SERIAL_NUMBER);

        $columns[] = $this->dbColumn(Entity::BANK_INSTRUMENT_ID);

        $columns[] = $this->dbColumn(Entity::BALANCE);

        $columns[] = $this->dbColumn(Entity::BALANCE_CURRENCY);

        $columns[] = $this->dbColumn(Entity::POSTED_DATE);

        $columns[] = $this->dbColumn(Entity::TRANSACTION_DATE);

        $columns[] = $this->dbColumn(Entity::BALANCE);

        $uniqueColumns = [];

        $uniqueColumns[] = $bankTransactionIdColumn = $this->dbColumn(Entity::BANK_TRANSACTION_ID);

        $uniqueColumns[] = $bankSerialNumberColumn = $this->dbColumn(Entity::BANK_SERIAL_NUMBER);

        $uniqueColumns[] = $bankTransactionDateColumn = $this->dbColumn(Entity::TRANSACTION_DATE);

        $uniqueColumns[] = $bankTransactionAmountColumn = $this->dbColumn(Entity::AMOUNT);

        $uniqueColumns[] = $bankTransactionChannelColumn = $this->dbColumn(Entity::CHANNEL);

        $uniqueColumns[] = $bankTransactionChannelColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);


        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->whereInMultiple($uniqueColumns, $records)
                    ->get($columns);
    }

    public function fetchUnlinkedBasRecords(string $accountNumber, string $channel, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::CHANNEL, $channel)
                    ->whereNull(Entity::TRANSACTION_ID)
                    ->orderBy(Entity::ID)
                    ->limit($limit)
                    ->get();
    }

    public function fetchUnlinkedBasRecordsBySourceEntity(string $accountNumber, string $channel, $limit)
    {
        return $this->newQuery()
            ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
            ->where(Entity::CHANNEL, $channel)
            ->whereNull(Entity::ENTITY_TYPE)
            ->whereNull(Entity::ENTITY_ID)
            ->orderBy(Entity::ID)
            ->limit($limit)
            ->get();
    }

    public function fetchByUtrForPayout(Payout\Entity $payout)
    {
        $query = $this->newQuery()
                      ->where(Entity::UTR, $payout->getUtr());

        $basEntities = $this->fetchForPayout($query, $payout);

        $externalLinkedBas = [];

        /** @var Entity $basEntity */
        foreach ($basEntities as $basEntity)
        {
            $source = $basEntity->source;

            if ($source->getEntity() === Constants\Entity::EXTERNAL)
            {
                $externalLinkedBas[] = $source;
            }
        }

        if (count($externalLinkedBas) === 1)
        {
            return $externalLinkedBas[0];
        }

        else if (count($externalLinkedBas) > 1)
        {
            $operation = 'Multiple BAS entities with same UTR';

            $data = [
                'channel'   => $payout->getChannel(),
                'amount'    => $payout->getAmount(),
                'payout_id' => $payout->getId(),
            ];

            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');

            throw new LogicException(
                'Found too many bas entities when fetched by UTR',
                ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE_BY_UTR,
                [
                    'payout_id'         => $payout->getId(),
                    'count'             => $basEntities->count(),
                ]);
        }

        return null;
    }

    public function fetchByCmsRefNumForPayout(Payout\Entity $payout)
    {
        // TODO: check uniqueness logic for cms_ref_no
        // JIRA ticket: https://razorpay.atlassian.net/browse/RX-695
        $fta = $payout->fundTransferAttempts->first();

        if ($fta === null)
        {
            return null;
        }

        $cmsRefNumber = $fta->getCmsRefNo();

        $query = $this->newQuery()
                      ->where(Entity::BANK_TRANSACTION_ID, $cmsRefNumber);

        $basEntities = $this->fetchForPayout($query, $payout);

        // for IFT mode
        if ($payout->getMode() === Payout\Mode::IFT)
        {
            $payoutInitiatedAt = $payout->getInitiatedAt();

            $filteredBasEntities = [];

            foreach ($basEntities as $basEntity)
            {
                /** @var Entity $basEntity */
                $postedDate = $basEntity->getPostedDate();

                $bankTimeBeforePostedDate = Carbon::createFromTimestamp($postedDate, Timezone::IST)
                                                  ->subHours(4)
                                                  ->getTimestamp();

                if (($payoutInitiatedAt > $bankTimeBeforePostedDate) and
                    ($payoutInitiatedAt < $postedDate))
                {
                    $filteredBasEntities[] = $basEntity;
                }
            }

            if (count($filteredBasEntities) === 1)
            {
                return $filteredBasEntities[0];
            }
            elseif (count($filteredBasEntities) > 1)
            {
                throw new LogicException(
                    'Found too many bas entities when fetch by cms reference number(mode IFT)',
                    ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE_BY_CMS_REF_NO_FOR_IFT,
                    [
                        'payout_id'         => $payout->getId(),
                        'count'             => $basEntities->count(),
                    ]);
            }

        }
        else // for modes other than IFT
        {
            if ($basEntities->count() === 1)
               {
                return $basEntities->first();
               }
            else if ($basEntities->count() > 1)
            {
                throw new LogicException(
                    'Found too many bas entities when fetch by cms reference number ',
                    ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE_BY_CMS_REF_NO_FOR_NON_IFT,
                    [
                        'payout_id'         => $payout->getId(),
                        'count'             => $basEntities->count(),
                    ]);
            }
        }

        return null;
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

        /** @var Base\PublicCollection $basEntities */
        $basEntities = $this->fetchForReversal($query, $reversal);

        // skipping the checks of finding more than 1 external linked bas
        if ($basEntities->count() > 1)
        {
            throw new LogicException(
                'Found too many bas entities of type credit when fetched by reversal UTR or payout UTR',
                ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE_BY_REVERSAL_UTR,
                [
                    'payout_id'         => $reversal->getId(),
                    'count'             => $basEntities->count(),
                ]);
        }

        return $basEntities;
    }

    public function fetchByCmsRefNumForReversal(Reversal\Entity $reversal)
    {
        /** @var Payout\Entity $payout */
        $payout = $reversal->entity;

        $cmsRefNumber = $payout->fundTransferAttempts->first()->getCmsRefNo();

        $query = $this->newQuery()
                      ->where(Entity::BANK_TRANSACTION_ID, $cmsRefNumber)
                      ->where(Entity::CREATED_AT, '>=', $payout->getCreatedAt());

        $basEntities = $this->fetchForReversal($query, $reversal);

        if ($basEntities->count() > 1)
        {
            throw new LogicException(
                'Found too many bas entities of type credit when fetched by cms reference number ',
                ErrorCode::SERVER_ERROR_MULTIPLE_BAS_FOR_REFERENCE_BY_REVERSAL_CMS_REF_NO,
                [
                    'reversal_id'       => $reversal->getId(),
                    'count'             => $basEntities->count(),
                ]);
        }

        return $basEntities;
    }

    protected function fetchForReversal($query, Reversal\Entity $reversal)
    {
        $payout = $reversal->entity;

        // reversal amount contains fee and tax but txn will
        // contain only absolute amount which will match with
        // payout amount. We are adding the clause of txn id
        // being not null to ensure behaviour of code remains
        // consistent in the new and old flow
        $basEntities = $query->where(Entity::TYPE, Type::CREDIT)
                             ->where(Entity::AMOUNT, $payout->getAmount())
                             ->where(Entity::ACCOUNT_NUMBER, $reversal->balance->getAccountNumber())
                             ->where(Entity::CHANNEL, $reversal->getChannel())
                             ->whereNotNull(Entity::TRANSACTION_ID)
                             ->get();

        return $basEntities;
    }

    protected function fetchForPayout($query, Payout\Entity $payout)
    {
        $basEntities = $query->where(Entity::TYPE, Type::DEBIT)
                             ->where(Entity::AMOUNT, $payout->getAmount())
                             ->where(Entity::ACCOUNT_NUMBER, $payout->balance->getAccountNumber())
                             ->where(Entity::CHANNEL, $payout->getChannel())
                             ->whereNotNull(Entity::TRANSACTION_ID)
                             ->get();

        return $basEntities;
    }

    public function fetchByUtrAndType(string $utr, string $type, string $accountNumber, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::CHANNEL, $channel)
                    ->where(Entity::UTR, $utr)
                    ->where(Entity::TYPE, $type)
                    ->get();
    }

    public function fetchByEntityIDAndEntityType(string $entityID, string $entityType, string $channel)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, $entityID)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->where(Entity::CHANNEL, $channel)
            ->first();
    }

    public function fetchBySourceEntityIDAndEntityType(string $entityID, string $entityType)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, $entityID)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->first();
    }
}
