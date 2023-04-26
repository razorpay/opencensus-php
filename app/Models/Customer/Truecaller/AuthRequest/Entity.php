<?php

namespace RZP\Models\Customer\Truecaller\AuthRequest;

use App;
use Carbon\Carbon;
use RZP\Models\Base\PublicEntity;
use RZP\Constants\Entity as ConstantsEntity;

/**
 * TruecallerAuthRequest entity's id is the "requestNonce" which will be used to trigger deeplink to truecaller app.
 * This is only stored in cache and not in db. everytime user wants to invoke the truecaller authentication
 * flow, we would generate a new TruecallerAuthRequest entity and return its id to client(checkout in this case).
 *
 * @property string $id             Primary key of this entity
 * @property string $context        Context for which we invoke this entity. in checkout's case its merchant_id
 * @property string $service        The client invoking this entity. ex: checkout
 * @property string $status         The status of the entity. possible values: active/verified
 * @property Carbon $created_at     Unix timestamp at which entity is created
 * @property string $user_profile   User's contact and email stored in json format
 * @property string $truecaller_status  The status of request_id.
 * Possible values: null/access_denied/user_rejected/used_another_number/resolved
 *
 *
 */
class Entity extends PublicEntity
{
    /**
     *  properties of this entity
     */
    const CONTEXT               = 'context';
    const SERVICE               = 'service';
    const STATUS                = 'status';
    const CREATED_AT            = 'created_at';
    const USER_PROFILE          = 'user_profile';
    const TRUECALLER_STATUS     = 'truecaller_status';

    /** Fields that are part of user_profile */
    const CONTACT               = 'contact';
    const EMAIL                 = 'email';

    protected $entity = ConstantsEntity::TRUECALLER_AUTH_REQUEST;

    /**
     * @inheritDoc
     */
    protected $public = [
        self::ID,
        self::CONTEXT,
        self::SERVICE,
        self::STATUS,
        self::TRUECALLER_STATUS,
    ];

    protected $fillable = [
        self::CONTEXT,
        self::SERVICE,
    ];

    /**
     * @var array Fields which will be generated during build
     * @see generateCreatedAt()
     */
    protected static $generators = [
        self::ID,
        self::CREATED_AT,
    ];

    /** @var array The default value for attributes to be set during building the entity */
    protected $defaults = [
        self::SERVICE => Constants::DEFAULT_SERVICE,
        self::STATUS => 'active',
        self::TRUECALLER_STATUS => null,
    ];

    /** @var array The attributes that should be visible in serialization. */
    protected $visible = [
        self::ID,
        self::CONTEXT,
        self::SERVICE,
        self::STATUS,
        self::CREATED_AT,
        self::USER_PROFILE,
        self::TRUECALLER_STATUS,
    ];

    /*** @var string[] The attributes that should be mutated to dates */
    protected $dates = [
      self::CREATED_AT,
    ];

    /** @var array The attributes that should be cast to native types. */
    protected $casts = [
        self::USER_PROFILE => 'json',
    ];

    // ------------------------------ GETTERS START ------------------------------

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getUserProfile()
    {
        return $this->getAttribute(self::USER_PROFILE);
    }

    public function getContext(): string
    {
        return $this->getAttribute(self::CONTEXT);
    }

    public function getService(): string
    {
        return $this->getAttribute(self::SERVICE);
    }

    // ------------------------------ GETTERS END ------------------------------
    // --------------------------------------------------------------------------------
    // ------------------------------ SETTERS START ------------------------------

    /**
     * @param string $status
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function setStatus(string $status): void
    {
        Validator::isValidStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setContext(string $context): void
    {
        $this->setAttribute(self::CONTEXT, $context);
    }

    public function setService(string $service): void
    {
        $this->setAttribute(self::SERVICE, $service);
    }

    public function setUserProfile($userProfile): void
    {
        if (isset($userProfile['contact']) === true)
        {
            $userProfile['contact'] = $this->app['encrypter']->encrypt($userProfile['contact']);
        }

        if (isset($userProfile['email']) === true)
        {
            $userProfile['email'] = $this->app['encrypter']->encrypt($userProfile['email']);
        }

        $this->setAttribute(self::USER_PROFILE, $userProfile);
    }

    // ------------------------------ SETTERS END ------------------------------
    // --------------------------------------------------------------------------------
    // ------------------------------ GENERATORS START ------------------------------

    protected function generateCreatedAt(): void
    {
        $createdAt = Carbon::now()->getTimestamp();

        $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    public function generateId(): void
    {
        $this->setAttribute(self::ID, bin2hex(random_bytes(30)));
    }

    // ------------------------------ GENERATORS END ------------------------------
    // --------------------------------------------------------------------------------

}
