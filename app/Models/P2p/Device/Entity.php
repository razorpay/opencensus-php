<?php

namespace RZP\Models\P2p\Device;

use Database\Factories\P2PDeviceFactory;
use RZP\Models\P2p\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\P2p\Client;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\P2p\Base\Traits;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entity extends Base\Entity
{
    use Traits\HasMerchant;
    use HasFactory;

    const CUSTOMER_ID  = 'customer_id';
    const MERCHANT_ID  = 'merchant_id';
    const CONTACT      = 'contact';
    const SIMID        = 'simid';
    const UUID         = 'uuid';
    const TYPE         = 'type';
    const OS           = 'os';
    const OS_VERSION   = 'os_version';
    const APP_NAME     = 'app_name';
    const IP           = 'ip';
    const GEOCODE      = 'geocode';
    const AUTH_TOKEN   = 'auth_token';

    const REGISTER_TOKEN    = 'register_token';
    const DEVICE_TOKEN      = 'device_token';
    const DEVICE            = 'device';
    /************** Entity Properties ************/

    protected $entity             = 'p2p_device';
    protected static $sign        = 'device';
    protected $generateIdOnCreate = true;
    protected static $generators  = [
        Entity::AUTH_TOKEN,
    ];

    protected $dates = [
        Entity::DELETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::CONTACT,
        Entity::SIMID,
        Entity::UUID,
        Entity::TYPE,
        Entity::OS,
        Entity::OS_VERSION,
        Entity::APP_NAME,
        Entity::IP,
        Entity::GEOCODE,
    ];

    protected $visible = [
        Entity::ID,
        Entity::ENTITY,
        Entity::CUSTOMER_ID,
        Entity::MERCHANT_ID,
        Entity::CONTACT,
        Entity::SIMID,
        Entity::UUID,
        Entity::TYPE,
        Entity::OS,
        Entity::OS_VERSION,
        Entity::APP_NAME,
        Entity::IP,
        Entity::GEOCODE,
        Entity::AUTH_TOKEN,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::ENTITY,
        Entity::CUSTOMER_ID,
        Entity::CONTACT,
        Entity::SIMID,
        Entity::UUID,
        Entity::TYPE,
        Entity::OS,
        Entity::OS_VERSION,
        Entity::APP_NAME,
        Entity::IP,
        Entity::GEOCODE,
        Entity::AUTH_TOKEN,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::CUSTOMER_ID  => null,
        Entity::CONTACT      => null,
        Entity::SIMID        => null,
        Entity::UUID         => null,
        Entity::TYPE         => null,
        Entity::OS           => null,
        Entity::OS_VERSION   => null,
        Entity::APP_NAME     => null,
        Entity::IP           => null,
        Entity::GEOCODE      => null,
    ];

    protected $casts = [
        Entity::ID           => 'string',
        Entity::CUSTOMER_ID  => 'string',
        Entity::MERCHANT_ID  => 'string',
        Entity::CONTACT      => 'string',
        Entity::SIMID        => 'string',
        Entity::UUID         => 'string',
        Entity::TYPE         => 'string',
        Entity::OS           => 'string',
        Entity::OS_VERSION   => 'string',
        Entity::APP_NAME     => 'string',
        Entity::IP           => 'string',
        Entity::GEOCODE      => 'string',
        Entity::AUTH_TOKEN   => 'string',
        Entity::DELETED_AT   => 'int',
        Entity::CREATED_AT   => 'int',
        Entity::UPDATED_AT   => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
    ];

    /***************** GENERATORS *****************/

    public function generateAuthToken()
    {
        $this->setAuthToken(gen_uuid());
    }

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setCustomerId(string $customerId)
    {
        return $this->setAttribute(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @return $this
     */
    public function setMerchantId(string $merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    /**
     * @return $this
     */
    public function setContact(string $contact)
    {
        return $this->setAttribute(self::CONTACT, $contact);
    }

    /**
     * @return $this
     */
    public function setSimid(string $simid)
    {
        return $this->setAttribute(self::SIMID, $simid);
    }

    /**
     * @return $this
     */
    public function setUuid(string $uuid)
    {
        return $this->setAttribute(self::UUID, $uuid);
    }

    /**
     * @return $this
     */
    public function setType(string $type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    /**
     * @return $this
     */
    public function setOs(string $os)
    {
        return $this->setAttribute(self::OS, $os);
    }

    /**
     * @return $this
     */
    public function setOsVersion(string $osVersion)
    {
        return $this->setAttribute(self::OS_VERSION, $osVersion);
    }

    /**
     * @return $this
     */
    public function setAppName(string $appName)
    {
        return $this->setAttribute(self::APP_NAME, $appName);
    }

    /**
     * @return $this
     */
    public function setIp(string $ip)
    {
        return $this->setAttribute(self::IP, $ip);
    }

    /**
     * @return $this
     */
    public function setGeocode(string $geocode)
    {
        return $this->setAttribute(self::GEOCODE, $geocode);
    }

    /**
     * @return $this
     */
    public function setAuthToken(string $authToken)
    {
        return $this->setAttribute(self::AUTH_TOKEN, $authToken);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::CUSTOMER_ID
     */
    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    /**
     * @return string self::MERCHANT_ID
     */
    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    /**
     * @return string self::CONTACT
     */
    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    /**
     * @return string self::CONTACT without initial plus
     */
    public function getFormattedContact()
    {
        return substr($this->getContact(), -12);
    }

    /**
     * @return string self::SIMID
     */
    public function getSimid()
    {
        return $this->getAttribute(self::SIMID);
    }

    /**
     * @return string self::UUID
     */
    public function getUuid()
    {
        return $this->getAttribute(self::UUID);
    }

    /**
     * @return string self::TYPE
     */
    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    /**
     * @return string self::OS
     */
    public function getOs()
    {
        return $this->getAttribute(self::OS);
    }

    /**
     * @return string self::OS_VERSION
     */
    public function getOsVersion()
    {
        return $this->getAttribute(self::OS_VERSION);
    }

    /**
     * @return string self::APP_NAME
     */
    public function getAppName()
    {
        return $this->getAttribute(self::APP_NAME);
    }

    /**
     * @return string self::APP_NAME
     */
    public function getAppFullName()
    {
        $map = [
            'com.razorpay'                      => 'Bajaj Finserv MARKETS',
            'in.bajajfinservmarkets.app'        => 'Bajaj Finserv MARKETS',
            'in.bajajfinservmarkets.app.uat'    => 'Bajaj Finserv MARKETS',
        ];

        return array_get($map, $this->getAppName(), 'Razorpay Mobile Application');
    }

    public function getSmsSender()
    {
        $map = [
            'com.razorpay'                  => 'BajajP',
            'in.bajajfinservmarkets.app'    => 'BajajP',
        ];

        return array_get($map, $this->getAppName(), 'RZRPAY');
    }

    /**
     * @return string self::IP
     */
    public function getIp()
    {
        return $this->getAttribute(self::IP);
    }

    /**
     * @return string self::GEOCODE
     */
    public function getGeocode()
    {
        return $this->getAttribute(self::GEOCODE);
    }

    /**
     * @return string self::AUTH_TOKEN
     */
    public function getAuthToken()
    {
        return $this->getAttribute(self::AUTH_TOKEN);
    }

    /***************** RELATIONS *****************/

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken\Entity::class, DeviceToken\Entity::DEVICE_ID);
    }

    public function deviceToken(Handle\Entity $handle)
    {
        return $this->deviceTokens()->handle($handle)->verified()->latest()->first();
    }

    public function client(Handle\Entity $handle)
    {
        return $handle->client(Client\Type::MERCHANT, $this->getMerchantId());
    }

    public function setPublicCustomerIdAttribute(& $input)
    {
        $input[self::CUSTOMER_ID] = Customer\Entity::getSignedId($input[self::CUSTOMER_ID]);
    }

    public function toArrayPartner($shouldMask = false): array
    {
        $array = $this->toArrayPublic();

        // authToken will be masked only in case of vpa webhook payload
        // as it will be removed from the payload in the later stage.
        if($shouldMask === true)
        {
            $array[self::AUTH_TOKEN] = mask_except_last4($array[self::AUTH_TOKEN]);

            return $array;
        }

        return array_except($array, [self::AUTH_TOKEN]);
    }

    protected static function newFactory(): P2PDeviceFactory
    {
        return P2PDeviceFactory::new();
    }
}
