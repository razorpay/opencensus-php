<?php

namespace RZP\Models\OfflineChallan;

use RZP\Constants\Entity as Constants;
use RZP\Models\Base;


class Entity extends Base\PublicEntity
{

    const VIRTUAL_ACCOUNT_ID = 'virtual_account_id';

    const STATUS = 'status';

    const CHALLAN_NUMBER = 'challan_number';

    const CHALLAN_LENGTH = 16;

    const BANK_NAME = 'bank_name';

    const CLIENT_CODE = 'client_code';

    protected $fillable = [
        self::VIRTUAL_ACCOUNT_ID,
        self::STATUS,
        self::CHALLAN_NUMBER,
        self::BANK_NAME,
        self::CLIENT_CODE,
    ];

    protected $public = [
        self::ID,
        self::CHALLAN_NUMBER,
        self::ENTITY,
        self::BANK_NAME,
        self::CLIENT_CODE,
    ];

    protected $ignoredRelations = [
        "virtualAccount"
    ];

    protected $generateIdOnCreate = true;

    protected $entity = Constants::OFFLINE_CHALLAN;


    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\VirtualAccount\Entity');
    }

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

}
