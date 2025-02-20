<?php

namespace RZP\Models\Payout\BankingAccount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Table;


class Entity extends Base\PublicEntity
{
    protected $entity = Constants\Entity::BANKING_ACCOUNTS;

    protected $table = Table::BANKING_ACCOUNTS;

    const ID                   = 'id';
    const MERCHANT_ID          = 'merchant_id';
    const BALANCE_ID           = 'balance_id';
    const ACCOUNT_NUMBER       = 'account_number';
    const ACCOUNT_TYPE         = 'account_type';
    const CHANNEL              = 'channel';
    const FTS_FUND_ACCOUNT_ID  = 'fts_fund_account_id';
    const STATUS               = 'status';
    const PAYOUT_SERVICE_ENABLED = 'payout_service_enabled';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';
    const ACCOUNT_TYPE_DIRECT  = 'direct';
    const ACCOUNT_TYPE_SHARED     = 'shared';
    const ONBOARDED_TIME       = 'onboarded_time';
}
