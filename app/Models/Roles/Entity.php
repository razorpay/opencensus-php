<?php

namespace RZP\Models\Roles;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\User\BankingRole;
use RZP\Models\RoleAccessPolicyMap;
use RZP\Models\Base\Traits\HardDeletes;


class Entity extends Base\PublicEntity
{
    use HardDeletes;

    protected $entity = 'roles';

    protected $table  = Table::ACCESS_CONTROL_ROLES;

    protected static $generators = [
        self::ID,
    ];

    const ID                                    = 'id';
    const NAME                                  = 'name';
    const DESCRIPTION                           = 'description';
    const TYPE                                  = 'type';
    const MERCHANT_ID                           = 'merchant_id';
    const PRODUCT                               = 'product';
    const ORG_ID                                = 'org_id';
    const CREATED_AT                            = 'created_at';
    const CREATED_BY                            = 'created_by';
    const UPDATED_AT                            = 'updated_at';
    const UPDATED_BY                            = 'updated_by';

    const CUSTOM                                = 'custom';
    const STANDARD                              = 'standard';
    const ACCESS_POLICY                         = 'access_policy';
    const ACCESS_POLICY_IDS                     = 'access_policy_ids';
    const ROLE_ID                               = 'role_id';
    const MEMBERS                               = 'members';

    const STANDARD_ROLE_MERCHANT_ID             = '100000Razorpay';
    const ORG_ID_FOR_ROLES                      = '100000razorpay';
    const USER_ID_FOR_SYSTEM                    = '10000000system';

    const COPY_DISABLE                          = 'copy_disable';

    public static $displayOrder = [
        BankingRole::OWNER,
        BankingRole::ADMIN,
        BankingRole::FINANCE_L1,
        BankingRole::FINANCE_L2,
        BankingRole::FINANCE_L3,
        BankingRole::FINANCE,
        BankingRole::OPERATIONS,
        BankingRole::CHARTERED_ACCOUNTANT,
        BankingRole::VIEW_ONLY,
        BankingRole::VENDOR,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::TYPE,
        self::MERCHANT_ID,
        self::CREATED_BY,
        self::UPDATED_BY,
        self::ORG_ID
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::TYPE,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::CREATED_BY,
        self::UPDATED_AT,
        self::UPDATED_BY,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::TYPE,
        self::MERCHANT_ID,
        self::ACCESS_POLICY,
        self::ACCESS_POLICY_IDS,
    ];

    /*protected $defaults = [
        self::ACCESS_POLICY_IDS => null,
    ];*/

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public static $rolesHiddenFromDashboard = [
        BankingRole::OWNER,
        BankingRole::VENDOR,
    ];

    public static $disableCopyForRoles = [
        BankingRole::ADMIN,
        BankingRole::OWNER
    ];

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class, self::MERCHANT_ID, Merchant\Entity::ID);
    }

    public function accessPolicy()
    {
        return $this->hasOne(RoleAccessPolicyMap\Entity::class, RoleAccessPolicyMap\Entity::ROLE_ID, self::ID);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }


    public static function stripRoleId(& $id)
    {
        $delimiter = 'role'.static::$delimiter;

        $ix = strpos($id, $delimiter);

        if ($ix === false)
        {
            return false;
        }

        $id = substr($id, $ix + 5);
    }

}
