<?php

namespace RZP\Models\P2p\Base\Libraries;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\P2p\Base\MorphMap;
use RZP\Models\P2p\Device;
use RZP\Base\JitValidator;
use Illuminate\Http\Request;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Exception\LogicException;
use Illuminate\Foundation\Application;
use RZP\Exception\BadRequestException;

class Context
{
    const APPLICATION               = 'application';

    const MERCHANT                  = 'merchant';

    const DEVICE                    = 'device';

    const HANDLE                    = 'handle';

    const REQUEST_ID                = 'request_id';

    const GATEWAY                   = 'gateway';

    const NAME                      = 'name';

    const ACTION                    = 'action';

    const INPUT                     = 'input';

    const OPTIONS_RULES = [
        self::REQUEST_ID                            => 'nullable|string|max:50',
        self::HANDLE                                => 'filled|string',
        self::DEVICE                                => 'array',
        self::DEVICE . '.' . Device\Entity::IP      => 'nullable|ipv4',
        self::DEVICE . '.' . Device\Entity::GEOCODE => 'nullable|string|max:20',
    ];

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var Device\Entity
     */
    protected $device;

    /**
     * @var Handle\Entity
     */
    protected $handle;

    /**
     * @var Device\DeviceToken\Entity
     */
    protected $deviceToken;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $gatewayData = [];

    public function loadWithRequest(Request $request)
    {
        MorphMap::boot();

        // Setting the options first as options will be use to resolve the context
        $this->setOptions(ContextMap::resolveRequestHeaders($request));

        // Handle is set in context from the options, each HTTP request will have handle specified
        $this->setHandle(app('repo')->p2p_handle->findOrFailPublic($this->options[self::HANDLE]));

        // As the context is loaded from HTTP request, we are using the basic auth
        // for merchant and device, later we will have to change this if we change the auth.
        // We are only going to set the context entities if the are available in basic auth.
        $basicAuth = app('basicauth');

        if (($basicAuth->getMerchant() instanceof Merchant\Entity) === false)
        {
            $this->throwContextException('Merchant has be context');
        }
        $this->setMerchant($basicAuth->getMerchant());

        if ($basicAuth->getDevice() instanceof Device\Entity)
        {
            $this->setDevice($basicAuth->getDevice());

            $deviceToken = $this->device->deviceTokens()
                                        ->handle($this->handle)
                                        ->verified()->latest()->first();

            // If there is no device token found, the context will fail
            if (($deviceToken instanceof Device\DeviceToken\Entity) === false)
            {
                $this->throwContextException('Device is not verified on given handle');
            }

            // Setting the device token with the device
            $this->setDeviceToken($deviceToken);
        }
        // Note:: We are not putting application as instance variable
        // to ensure that context is independent of application container.
    }

    /**
     * @return Merchant\Entity
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * @param Merchant\Entity $merchant
     */
    public function setMerchant(Merchant\Entity $merchant)
    {
        if ($this->handle->isAllowedToMerchant($merchant->getId()) === false)
        {
            throw new LogicException('Merchant is not allowed to use the handle');
        }

        $this->merchant = $merchant;
    }

    /**
     * @return Device\Entity
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param Device\Entity $device
     */
    public function setDevice(Device\Entity $device)
    {
        // Basic auth already takes care of device owner, here we are only enforcing it.
        if ($this->merchant->getId() !== $device->getMerchantId())
        {
            $this->throwContextException('Device does not belong to merchant in context');
        }

        $this->device = $device;
    }

    /**
     * @return Handle\Entity
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param Handle\Entity $handle
     */
    public function setHandle(Handle\Entity $handle)
    {
        $this->handle = $handle;
    }

    /**
     * @return Device\DeviceToken\Entity
     */
    public function getDeviceToken()
    {
        return $this->deviceToken;
    }

    public function setDeviceToken(Device\DeviceToken\Entity $deviceToken)
    {
        $this->deviceToken = $deviceToken;
    }

    /**
     * @return ArrayBag
     */
    public function getOptions(): ArrayBag
    {
        return $this->options;
    }

    /**
     * @param ArrayBag $options
     */
    public function setOptions(ArrayBag $options)
    {
        $validator = new JitValidator();

        $validator->rules(self::OPTIONS_RULES)
                  ->caller($this)
                  ->setStrictFalse()
                  ->input($options->toArray())
                  ->validate();

        $this->options = $options;
    }

    /**
     * Gateway action and input might be different from actual action,
     * and input, thus we are wrapping it into Gateway Options
     *
     * @param string $name
     * @param string $action
     * @param ArrayBag $input
     */
    public function setGatewayData(string $name, string $action, ArrayBag $input)
    {
        $data = new ArrayBag([
            self::NAME      => $name,
            self::ACTION    => $action,
            self::INPUT     => $input,
        ]);

        $this->gatewayData = $data;
    }

    public function getGatewayData(): ArrayBag
    {
        return $this->gatewayData;
    }

    /**
     * @return string
     * @throws BadRequestException
     */
    public function getContextType()
    {
        // If handle is empty, we can have some internal task run like cron
        if (empty($this->getHandle()) === true)
        {
            return self::APPLICATION;
        }

        // If handle is there with device, it will be considered device context
        if (empty($this->getDevice()) === false)
        {
            return self::DEVICE;
        }

        // If device is not there but handle is, it is merchant context
        if (empty($this->getMerchant()) === false)
        {
            return self::MERCHANT;
        }

        $this->throwContextException('Could not resolve context type');
    }

    /**
     * Check if context is Application
     *
     * @return bool
     * @throws BadRequestException
     */
    public function isContextApplication(): bool
    {
        return ($this->getContextType(true) === self::APPLICATION) or ($this->isContextMerchant());
    }

    /**
     * Check if context is Merchant
     *
     * @return bool
     * @throws BadRequestException
     */
    public function isContextMerchant(): bool
    {
        return ($this->getContextType(true) === self::MERCHANT) or ($this->isContextDevice());
    }

    /**
     * Check if context is Device
     *
     * @return bool
     * @throws BadRequestException
     */
    public function isContextDevice(): bool
    {
        return ($this->getContextType(true) === self::DEVICE);
    }

    /**
     * Return Code in Handle from the context
     *
     * @return string
     */
    public function handleCode(): string
    {
        return $this->handle->getCode();
    }

    public function validateMerchant(Merchant\Entity $merchant)
    {
        if ($this->merchant->getId() !== $merchant->getId())
        {
            $this->throwContextException('Wrong merchant in context');
        }
    }

    /**
     * @param $message
     * @throws BadRequestException
     */
    public function throwContextException($message)
    {
        throw new BadRequestException(ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED, $message);
    }
}
