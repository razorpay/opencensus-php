<?php

namespace RZP\Models\Invitation;

use App;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;

class Entity extends Base\PublicEntity
{
    use SoftDeletes, AsvGetAttribute;

    const USER_ID           = 'user_id';
    // Note: name is stored inside metadata
    const METADATA          = 'metadata';
    const NAME              = 'name';
    const EMAIL             = 'email';
    const CONTACT_MOBILE    = 'contact_mobile';
    const TOKEN             = 'token';
    const ROLE              = 'role';
    const ROLE_NAME         = 'role_name';
    const DELETED_AT        = 'deleted_at';
    const PRODUCT           = 'product';
    const MERCHANT_ID       =  'merchant_id';

    const INVITATIONTYPE = 'invitation_type';
    const INVITATION_DETAILS = 'invitation_details';

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
        self::CONTACT_MOBILE,
        self::METADATA,
        self::ROLE,
        self::USER_ID,
        self::PRODUCT,
        self::MERCHANT_ID,
        self::IS_DRAFT,
        self::ROLE_NAME,
        self::METADATA,
    ];

    protected $fillable = [
        self::ROLE,
        self::EMAIL,
        self::CONTACT_MOBILE,
        self::METADATA,
        self::TOKEN,
        self::PRODUCT,
        self::IS_DRAFT,
        self::METADATA,
        self::MERCHANT_ID,
    ];

    protected $casts = [
        self::METADATA                  => 'array',
    ];

    protected $defaults = [
        self::METADATA                  => null
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

    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

    public function getMetadata()
    {
        if (empty($this->getAttribute(self::METADATA)) === true) return [];
        return $this->getAttribute(self::METADATA);
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
            $roleName = (new \RZP\Models\Roles\Service())->getRoleNameUsingExperiment($this->getAttribute(self::ROLE));

            $attributes[self::ROLE_NAME] = $roleName;
        }
    }

    public function toArrayUser()
    {
        $app = App::getFacadeRoot();

        $attributes = [
            self::ID                => $this->getAttribute(self::ID),
            self::EMAIL             => $this->getAttribute(self::EMAIL),
            self::CONTACT_MOBILE    => $this->getAttribute(self::CONTACT_MOBILE),
            self::METADATA          => $this->getMetadata(),
            self::ROLE              => $this->getAttribute(self::ROLE),
            self::USER_ID           => $this->getAttribute(self::USER_ID),
            self::MERCHANT_ID       => $this->getAttribute(self::MERCHANT_ID),
            self::PRODUCT           => $this->getAttribute(self::PRODUCT),
            self::IS_DRAFT          => $this->getAttribute(self::IS_DRAFT),
            self::MERCHANT_NAME     => $this->merchant->getName(),
        ];

        if($this->getAttribute(self::PRODUCT) ===  Product::BANKING)
        {
            $attributes[self::ROLE_NAME] = (new \RZP\Models\Roles\Service())->getRoleNameUsingExperiment($this->getAttribute(self::ROLE));
        }

        return $attributes;
    }

    public function toArrayInternal()
    {
        $attributes = $this->toArrayPublic();

        $attributes[self::TOKEN] = $this->getAttribute(self::TOKEN);

        return $attributes;
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }

    public function toArrayPublicForUserService()
    {
        $attributes = $this->toArrayPublic();

        $attributes[self::TOKEN] = $this->getAttribute(self::TOKEN);
        $attributes[self::CREATED_AT] = $this->getAttribute(self::CREATED_AT);
        $attributes[self::UPDATED_AT] = $this->getAttribute(self::UPDATED_AT);

        return $attributes;
    }

   }
