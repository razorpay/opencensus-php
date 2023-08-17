<?php


namespace RZP\Models\CreditTransfer;

use RZP\Models\Base;
use RZP\Models\CreditTransfer;

class Repository extends Base\Repository
{
    protected $entity = 'credit_transfer';

    public function findCreditTransferBySourceId($sourceId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $sourceId)
                    ->first();
    }

    /**
     * @param $id
     * @return string
     */
    public function findBalanceIdById(string $id)
    {
        $balanceId = $this->repo->credit_transfer->dbColumn(CreditTransfer\Entity::BALANCE_ID);

        $result = $this->newQuery()
                       ->select($balanceId)
                       ->where(Entity::ID, '=', $id)
                       ->get();

        if (empty($result) === false)
            return $result->first()[CreditTransfer\Entity::BALANCE_ID];

        return '';
    }

    /**
     * @param $id
     * @return string
     */
    public function findChannelById(string $id)
    {
        $channel = $this->repo->credit_transfer->dbColumn(CreditTransfer\Entity::CHANNEL);

        $result = $this->newQuery()
                       ->select($channel)
                       ->where(Entity::ID, '=', $id)
                       ->get();

        if (empty($result) === false)
            return $result->first()[CreditTransfer\Entity::CHANNEL];

        return '';
    }
}
