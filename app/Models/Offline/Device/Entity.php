<?php

namespace RZP\Models\Offline\Device;

use Carbon\Carbon;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID               = 'id';
    const MERCHANT_ID      = 'merchant_id';
    const SERIAL_NUMBER    = 'serial_number';
    const FINGERPRINT      = 'fingerprint';
    const TYPE             = 'type';
    const MANUFACTURER     = 'manufacturer';
    const MODEL            = 'model';
    const OS               = 'os';
    const FIRMWARE_VERSION = 'firmware_version';
    const FEATURES         = 'features';
    const STATUS           = 'status';
    const PUSH_TOKEN       = 'push_token';
    const ACTIVATION_TOKEN = 'activation_token';
    const ACTIVATED_AT     = 'activated_at';
    const LINKED_AT        = 'linked_at';
    const REGISTERED_AT    = 'registered_at';

    protected static $sign = 'dev';

    protected $generateIdOnCreate = true;

    protected $entity = 'offline_device';

    protected $fillable = [
        self::SERIAL_NUMBER,
        self::FINGERPRINT,
        self::OS,
        self::FIRMWARE_VERSION,
        self::TYPE,
        self::MANUFACTURER,
        self::MODEL,
        self::FEATURES,
        self::PUSH_TOKEN,
    ];

    protected $defaults = [
        self::STATUS => 'created',
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SERIAL_NUMBER,
        self::TYPE,
        self::ACTIVATION_TOKEN,
        self::STATUS,
    ];

    protected static $generators = [
        self::ACTIVATION_TOKEN,
    ];

    protected $appends = [
        self::PUBLIC_ID,
    ];

    protected $unsetInitiateActivationInput = [
        self::SERIAL_NUMBER,
    ];

    public function register($input)
    {
        $this->validateInput('register', $input);

        $this->generate($input);

        $this->unsetInput('register', $input);

        $this->fill($input);

        return $this;
    }

    public function getActivationToken()
    {
        return $this->getAttribute(self::ACTIVATION_TOKEN);
    }

    // ----------------------- Setters -----------------------

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setActivated()
    {
        $this->setAttribute(self::STATUS, 'activated');

        $this->setAttribute(self::ACTIVATED_AT, Carbon::now()->getTimestamp());
    }

    public function setLinkedAt()
    {
        $this->setAttribute(self::LINKED_AT, Carbon::now()->getTimestamp());
    }

    // ----------------------- Generators -----------------------
    protected function generateActivationToken($input)
    {
        $activationToken = $input['serial_number'];

        $this->setAttribute(self::ACTIVATION_TOKEN, $activationToken);
    }

    // ----------------------- Relations -----------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }
}
