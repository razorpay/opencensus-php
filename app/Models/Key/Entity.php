<?php

namespace RZP\Models\Key;

use App;
use Crypt;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Base\QueryCache\Cacheable;

class Entity extends Base\PublicEntity
{
    use Cacheable;

    const ID            = 'id';
    const MERCHANT_ID   = 'merchant_id';
    const SECRET        = 'secret';
    const EXPIRED_AT    = 'expired_at';
    const OTP           = 'otp';
    const TOKEN         = 'token';

    const SECRET_LENGTH = 24;

    protected $entity = 'key';

    protected $generateIdOnCreate = true;

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::EXPIRED_AT
    ];

    // These are not persisted in DB and are only used during migration of credentials of internal apps from applications_v2 config.
    const OWNER_ID   = 'owner_id';
    const OWNER_TYPE = 'owner_type';
    const ROLE_NAMES = 'role_names';
    const DOMAIN     = 'domain';

    const OWNER_TYPE_MERCHANT    = "merchant";
    const OWNER_TYPE_APPLICATION = "application";
    const DOMAIN_RAZORPAY        = "razorpay";

    // These are transient properties and will not be persisted in DB, i.e these are not attributes in this entity.
    public $ownerType = "";
    public $ownerId   = "";
    public $roleNames = [];

    /**
     * 86400 sec or more accurately 24 hours.
     * When a key is rolled over, by default
     * the old key remains valid for 24 hours.
     */
    const DEFAULT_KEY_EXPIRY_TIME_ON_ROLL = 86400;

    protected $hidden = [
        self::SECRET
    ];

    protected $defaults = [
        self::EXPIRED_AT => null
    ];

    public function merchant()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\Entity');
    }

    public function getSecret()
    {
        return $this->getAttribute(self::SECRET);
    }

    public function setOwnerId(string $ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * owner_id is a transient property and this will return an empty string if it has not been set via setOwnerId.
     * @return string
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setOwnerType(string $ownerType)
    {
        $this->ownerType = $ownerType;
    }

    /**
     * owner_type is a transient property and this will return an empty string if it has not been set via setOwnerType.
     * @return string
     */
    public function getOwnerType()
    {
        return $this->ownerType;
    }

    public function setRoleNames(array $roleNames)
    {
        $this->roleNames = $roleNames;
    }

    /**
     * role_names is a transient property and this will return an empty array if it has not been set via setRoleNames.
     * @return array
     */
    public function getRoleNames()
    {
        return $this->roleNames;
    }

    public function getPublicId()
    {
        $app = App::getFacadeRoot();

        $mode = $app['basicauth']->getMode();

        return 'rzp_' . $mode . '_' . $this->getKey();
    }

    public function getPublicKey($mode = null)
    {
        if ($mode === null)
        {
            $app = App::getFacadeRoot();

            $mode = $app['basicauth']->getMode();
        }

        return 'rzp_' . $mode . '_' . $this->getKey();
    }

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($query)
        {
            $query->whereNull(self::EXPIRED_AT)
                  ->orWhere(self::EXPIRED_AT, '>', time());
        });
    }

    public function isExpiredOrExpiring()
    {
        return ($this->attributes[self::EXPIRED_AT] !== null);
    }

    public function isExpired()
    {
        $expiredAt = $this->getAttribute(self::EXPIRED_AT);

        if ($expiredAt === null)
        {
            return false;
        }

        return ($expiredAt <= time());
    }

    public function checkAndSetExpired($delay = false)
    {
        if ($this->isExpiredOrExpiring())
        {
            $errorCode = null;

            if ($this->isExpired())
            {
                $errorCode = ErrorCode::BAD_REQUEST_KEY_EXPIRED;
            }
            else
            {
                $errorCode = ErrorCode::BAD_REQUEST_KEY_EXPIRING_SOON;
            }

            throw new Exception\BadRequestException($errorCode);
        }

        $this->setExpired($delay);
    }

    protected function setExpired($delay = false)
    {
        $delaySeconds = 0;

        if ($delay === true)
        {
            // Delay by pre-specified time
            $delaySeconds = self::DEFAULT_KEY_EXPIRY_TIME_ON_ROLL;
        }

        $expiredAt = time() + $delaySeconds;
        $this->setAttribute(self::EXPIRED_AT, $expiredAt);
    }

    public function getExpiredAt()
    {
        return $this->getAttribute(self::EXPIRED_AT);
    }

    /**
     * generates the key secret uses a
     * cryptographically strong algorithm.
     * Sets it's hash in object and returns the secret.
     */
    public function generateSecret()
    {
        $secret = '';
        $x = range(1,8);
        foreach ($x as $n)
        {
            $hex = bin2hex(random_bytes(4));
            $dec = hexdec($hex);

            // Convert the random decimal generated to base 62
            $partial = self::base62($dec);
            $partial = substr($partial, -4);
            $secret .= $partial;
        }

        if (strlen($secret) > self::SECRET_LENGTH)
        {
            $secret = substr($secret, 0, self::SECRET_LENGTH);
        }

        $hash = Crypt::encrypt($secret, true, $this);
        $this->setAttribute(self::SECRET, $hash);

        assertTrue(strlen($secret) === self::SECRET_LENGTH);

        return $secret;
    }

    public static function generateUniqueId()
    {
        $len = self::ID_LENGTH;

        $id = '';
        $x = range(1,4);
        foreach ($x as $n)
        {
            $hex = bin2hex(random_bytes(4));
            $dec = hexdec($hex);

            // Convert the random decimal generated to base 62
            $partial = self::base62($dec);
            $partial = substr($partial, -4);
            $id .= $partial;
        }

        $id = substr($id, -1 * $len);

        assertTrue(strlen($id) === self::ID_LENGTH);

        return $id;
    }

    public static function stripSign(& $id)
    {
        $app = App::getFacadeRoot();

        $mode = $app['basicauth']->getMode();

        $prefix = 'rzp_' . $mode . '_';

        if (strpos($id, $prefix) === false)
        {
            return false;
        }

        $len = strlen($prefix);

        $id = substr($id, $len);

        return true;
    }

    /**
     * Serializes entity with public attributes and secret, exceptionally, and returns.
     * @return array
     */
    public function toArrayPublicWithSecret(): array
    {
        return $this->toArrayPublic() + [self::SECRET => $this->getDecryptedSecret()];
    }

    public function getDecryptedSecret()
    {
        return Crypt::decrypt($this->getSecret(), true, $this);
    }
}
