<?php

namespace RZP\Models\FundTransfer\Batch;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'batch_fund_transfer';

    protected static $appFetchParamRules = [
        'date' => 'integer|digits:8',
        'type' => 'string|max:10',
    ];

    protected static $fetchExtraParamRules = [
        'date' => 'integer|digits:8'
    ];

    public function getSettlementForToday($channel = 'kotak')
    {
        return $this->getSettlementForTodayQuery($channel)->first();
    }

    public function getSettlementForTodayOrFail($channel = 'kotak')
    {
        return $this->getSettlementForTodayQuery($channel)->firstOrFail();
    }

    protected function getSettlementForTodayQuery($channel)
    {
        $timestamp = Daily\Entity::getTodayTimestamp();

        return Daily\Entity::where(Daily\Entity::DATE, '=', $timestamp)
                           ->where(Daily\Entity::CHANNEL, '=', $channel);
   }

    protected function validateAdditional(array $params)
    {
        $this->validateDate($params);
    }

    public function getIfFeesIsNull()
    {
        return $this->newQuery()
                    ->whereNull(Daily\Entity::FEES)->get();
    }

    protected function validateDate($params)
    {
        if (isset($params['date']) === false)
        {
            return;
        }

        $parts = str_split($params['date'], 2);

        $res = checkdate($parts[0], $parts[1], $parts[2].$parts[3]);

        if ($res === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Date is not provided in ddmmyyyy format.');
        }
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        if (isset($params['date']))
        {
            $timestamp = Carbon::createFromFormat('dmY', $params['date'], 'Asia/Kolkata')->timestamp;

            $query->where(Daily\Entity::DATE, '=', $params['date']);
        }

        return $query;
    }

    public function getIfServiceTaxIsNullOrZero()
    {
        return $this->newQuery()
                    ->where(Entity::SERVICE_TAX, '=', '0')
                    ->orWhereNull(Entity::SERVICE_TAX)
                    ->get();
    }

    public function updateBatch(string $batchId, array $updateAttributes)
    {
        Entity::verifyIdAndSilentlyStripSign($batchId);

        $this->newQuery()
             ->where(Entity::ID, '=', $batchId)
             ->update($updateAttributes);
    }
}
