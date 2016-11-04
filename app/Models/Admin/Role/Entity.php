<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const ORG_ID            = 'org_id';

    protected $table = Constants\Table::ROLE;

    protected $entity = 'role';

    protected static $sign = 'role';

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::NAME,
        self::DESCRIPTION,
    ];

    /**
     * Returns all admins in org for role.
     *
     **/
    public function admins()
    {

    }

    /**
     * Returns organisation for role
     *
     **/
    public function org()
    {

    }

    /**
     * Returns permissions for role
     *
     **/
    public function permissions()
    {

    }
}
