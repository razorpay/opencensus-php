<?php

namespace Models\Settlement\Daily;

use Carbon\Carbon;
use EE\Exception;
use Models\Base;
use Models\Settlement\Daily;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Daily';

    protected static $fetchExtraParamRules = array(
        'date' => 'integer|digits:8');

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
}