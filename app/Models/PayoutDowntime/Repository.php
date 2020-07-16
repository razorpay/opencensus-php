<?php

namespace RZP\Models\PayoutDowntime;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Base\PublicCollection as PublicCollection;
use RZP\Exception;

class Repository extends Base\Repository
{
    protected $entity = Table::PAYOUT_DOWNTIMES;

    public function getAllActiveDowntimesByStatus(string $status): PublicCollection
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $startTime = $this->dbColumn(Entity::START_TIME);

        $endTime = $this->dbColumn(Entity::END_TIME);

        $query = $this->newQuery()
                      ->where(Entity::STATUS, $status)
                      ->orderBy(Entity::UPDATED_AT, 'desc');

        $query->where(function ($query) use ($endTime, $currentTimestamp)
        {
            $query->whereNull($endTime)
                  ->orWhere($endTime, '>', $currentTimestamp);
        });

        $query->where($startTime, '<=', $currentTimestamp);

        return $query->get();

    }

    public function findAllUserIdsForMerchantIds(array $merchantIds): array
    {
        if (empty($merchantIds) === true)
        {
            return array();
        }
        $userIdsCollection = $this->repo->merchant_user->fetchAllBankingUserIdsForMerchantIds($merchantIds);

        $userIds = $userIdsCollection->getStringAttributesByKey('user_id');

        $userIds = array_keys($userIds);

        // if it's integer like string keys, then array_keys will convert
        // those to integers. Let's re-map to string
        return array_map('strval', $userIds);
    }

    public function fetchUserEmails(array $userIds): array
    {
        return $this->repo->user->fetchUserEmails($userIds);
    }

    public function updateEmailStatus(string $id, string $status)
    {
        $downtimeId = Entity::verifyIdAndStripSign($id);

        $downtime = $this->repo->payout_downtimes->findOrFailPublic($downtimeId);

        if($downtime->getStatus() === Constants::ENABLED)
        {
            //Field will be updated to one of Processing, Sent or Failed for Enabled state.
            $downtime->setEnabledEmailStatus($status);
        }
        else
        {
            //Field will be updated to one of Processing, Sent or Failed for Disabled state.
            $downtime->setDisabledEmailStatus($status);
        }

        $this->repo->payout_downtimes->saveOrFail($downtime);
    }

    //fetching contract is going to sort enabled first followed by disabled.
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::STATUS, 'desc')
              ->orderBy(Common::CREATED_AT, 'desc')
              ->orderBy(Common::ID, 'desc');
    }

    public function saveOrFailEntity(Entity $downtime)
    {
        if (empty($downtime->getEndTime()) === false and
            (int)$downtime->getStartTime() > (int)$downtime->getEndTime())
        {
            throw new Exception\BadRequestValidationFailureException('End time should be greater than start time');
        }

        $this->repo->payout_downtimes->saveOrFail($downtime);
    }

    public function fetchActiveCurrentAccountForMerchantIds(array $merchantId, string $channel, string $accountType): array
    {
        return $this->repo->banking_account->fetchActiveCurrentAccountForMerchantIds($merchantId, $channel, $accountType);
    }

    public function fetchActiveVirtualAccountForMerchantIds(array $merchantIds)
    {
        return $this->repo->virtual_account->fetchActiveVirtualAccountForMerchantIds($merchantIds);
    }

    public function fetchActiveRblAccountForMerchantIds(array $merchantIds)
    {
        return $this->repo->banking_account->fetchActiveCurrentAccountForMerchantIds($merchantIds, strtolower(Constants::RBL), Constants::CURRENT);
    }

    public function fetchMerchantIdsInChunk($skip, $limit)
    {
        return $this->repo->merchant->fetchMerchantIdsInChunk($skip, $limit);
    }

}
