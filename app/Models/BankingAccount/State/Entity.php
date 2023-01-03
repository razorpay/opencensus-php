<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\User;
use RZP\Models\Admin\Admin;
use RZP\Models\BankingAccount;

class Entity extends Base\PublicEntity
{
    const BANKING_ACCOUNT = 'banking_account';

    const STATUS = 'status';

    const SUB_STATUS = 'sub_status';

    const BANK_STATUS = 'bank_status';

    const ASSIGNEE_TEAM = 'assignee_team';

    const ADMIN_ID = 'admin_id';

    const USER_ID = 'user_id';

    const USER = 'user';

    const BANKING_ACCOUNT_ID = 'banking_account_id';

    protected $entity = 'banking_account_state';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::STATUS,
        self::SUB_STATUS,
        self::BANK_STATUS,
        self::ASSIGNEE_TEAM,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::USER,
    ];

    protected $visible = [
        self::ID,
        self::STATUS,
        self::SUB_STATUS,
        self::BANK_STATUS,
        self::ASSIGNEE_TEAM,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::CREATED_AT,
        self::USER,
    ];

    public $public = [
        self::ID,
        self::STATUS,
        self::SUB_STATUS,
        self::BANK_STATUS,
        self::ASSIGNEE_TEAM,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::CREATED_AT,
        self::USER,
    ];
    public function bankingAccount()
    {
        return $this->belongsTo(BankingAccount\Entity::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin\Entity::class);
    }

    // Request coming from RBL Partner LMS
    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function getBankingAccountId()
    {
        return $this->getAttribute(self::BANKING_ACCOUNT_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getSubStatus()
    {
        return $this->getAttribute(self::SUB_STATUS);
    }

    public function getAssigneeTeam()
    {
        return $this->getAttribute(self::ASSIGNEE_TEAM);
    }
}
