<?php

namespace RZP\Models\Invitation;

use App;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const USER_ID      = 'user_id';
    const EMAIL        = 'email';
    const TOKEN        = 'token';
    const ROLE         = 'role';
    const ROLE_NAME    = 'role_name';
    const DELETED_AT   = 'deleted_at';
    const PRODUCT      = 'product';
    const INVITATIONTYPE = 'invitation_type';

    // Other constants
    const ACTION        = 'action';
    const SENDER_NAME   = 'sender_name';
    const MERCHANT_NAME = 'merchant_name';
    const IS_DRAFT      = 'is_draft';

    const TOKEN_LENGTH = 40;

    protected $entity  = 'invitation';

    public $incrementing = true;

    protected $public = [
        self::ID,
        self::EMAIL,
        self::ROLE,
        self::USER_ID,
        self::PRODUCT,
        self::MERCHANT_ID,
        self::IS_DRAFT,
        self::ROLE_NAME
    ];

    protected $fillable = [
        self::ROLE,
        self::EMAIL,
        self::TOKEN,
        self::PRODUCT,
        self::IS_DRAFT,
    ];

    protected $hidden = [
        self::TOKEN
    ];

    protected static $modifiers = [
        self::EMAIL,
    ];

    protected $publicSetters    = [
        self::ROLE_NAME
    ];

    // --------------------- Modifiers ---------------------------------------------

    /**
     * Modifies the email to have lower.
     * @param $input
     */
    protected function modifyEmail(& $input)
    {
        if (empty($input[self::EMAIL]) === false)
        {
            $input[self::EMAIL] = mb_strtolower($input[self::EMAIL]);
        }
    }

    // --------------------- Modifiers Ends ----------------------------------------

    /**
     * Get the merchant that owns the invitation.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    /**
     * Get the user that received the invitation.
     */
    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function getRole()
    {
        return $this->getAttribute(self::ROLE);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    public function getProduct()
    {
        return $this->getAttribute(self::PRODUCT);
    }

    public function getDraftState()
    {
        return $this->getAttribute(self::IS_DRAFT);
    }

    public function setDraftState($state)
    {
        $input[self::IS_DRAFT] = $state ;
    }

    public function setPublicRoleNameAttribute(array & $attributes)
    {
        if($this->getAttribute(self::PRODUCT) ===  Product::BANKING)
        {
            $app = App::getFacadeRoot();

            $roleName = $app['repo']->roles->fetchRoleName($this->getAttribute(self::ROLE));

            $attributes[self::ROLE_NAME] = $roleName;
        }
    }

    public function toArrayUser()
    {
        $app = App::getFacadeRoot();

        $attributes = [
            self::ID            => $this->getAttribute(self::ID),
            self::EMAIL         => $this->getAttribute(self::EMAIL),
            self::ROLE          => $this->getAttribute(self::ROLE),
            self::USER_ID       => $this->getAttribute(self::USER_ID),
            self::MERCHANT_ID   => $this->getAttribute(self::MERCHANT_ID),
            self::PRODUCT       => $this->getAttribute(self::PRODUCT),
            self::IS_DRAFT      => $this->getAttribute(self::IS_DRAFT),
            self::MERCHANT_NAME => $this->merchant->getName(),
        ];

        if($this->getAttribute(self::PRODUCT) ===  Product::BANKING)
        {
            $attributes[self::ROLE_NAME] = $app['repo']->roles->fetchRoleName($this->getAttribute(self::ROLE));
        }

        return $attributes;
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
