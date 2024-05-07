<?php

namespace RZP\Models\Admin\AdminsMeta;

use RZP\Models\Admin\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const UNIQUE_IDENTIFIER    = 'unique_identifier';
    const ADMIN_ID             = 'admin_id';
    const AUTH_MODE            = 'auth_mode';
    const USER_DISABLED_AT     = 'user_disabled_at';

    protected $entity = 'admins_meta';

    protected static $sign = 'am';

    protected $fillable = [
        self::UNIQUE_IDENTIFIER,
        self::ADMIN_ID,
        self::AUTH_MODE,
        self::USER_DISABLED_AT
    ];

    protected $public = [
        self::ID,
        self::UNIQUE_IDENTIFIER,
        self::ADMIN_ID,
        self::AUTH_MODE,
        self::USER_DISABLED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $visible = [
        self::ID,
        self::UNIQUE_IDENTIFIER,
        self::ADMIN_ID,
        self::AUTH_MODE,
        self::USER_DISABLED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
      self::USER_DISABLED_AT => null
    ];

    public function build(array $input = array())
    {

        $this->getValidator()->validateInput('create_admins_meta', $input);

        $this->generate($input);

        $this->unsetInput('create', $input);

        $this->fill($input);

        return $this;
    }

    public function getInputFields()
    {
        return $this->fillable;
    }

}
