<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'invoice';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::STATUS              => 'sometimes|string',
        Entity::SMS_STATUS          => 'sometimes|string',
        Entity::EMAIL_STATUS        => 'sometimes|string',
        Entity::CUSTOMER_EMAIL      => 'sometimes|email',
        Entity::CUSTOMER_CONTACT    => 'sometimes|string',
        Entity::CUSTOMER_ID         => 'sometimes|alpha_num',
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::STATUS              => 'sometimes|string',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
    ];

    public function getInvoicesForNotification($medium)
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where($medium . '_status', '=', Status::PENDING)
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::SCHEDULED_AT, '<=', $currentTime)
                    ->get();
    }

    public function getExpiredInvoices()
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::DUE_BY, '<', $currentTime)
                    ->get();
    }
}
