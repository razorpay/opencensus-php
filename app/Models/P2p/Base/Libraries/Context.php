<?php

namespace RZP\Models\P2p\Base\Libraries;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
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

    const OPTIONS_RULES = [
        self::REQUEST_ID                            => 'nullable|string|max:50',
        self::HANDLE                                => 'filled|string',
        self::DEVICE                                => 'array',
        self::DEVICE . '.' . Device\Entity::IP      => 'nullable|ipv4',
        self::DEVICE . '.' . Device\Entity::GEOCODE => 'nullable|string|max:20',
    ];
    /**
     * @var Application
     */
    protected $app;

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
     * @var array
     */
    protected $options = [];

    /**
     * Any P2P api whether public
     *
     * Context constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function loadWithRequest(Request $request)
    {
        $this->setOptions(ContextMap::resolveRequestHeaders($request));
        $this->setMerchant($this->app['basicauth']->getMerchant());
        $this->setDevice($this->app['basicauth']->getDevice());
        $this->setHandle($this->app['repo']->p2p_handle->findOrFailPublic($this->options[self::HANDLE]));
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
    public function setMerchant($merchant)
    {
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
    public function setDevice($device)
    {
        if (($device instanceof Device\Entity) === false)
        {
            return;
        }

        if ($this->merchant->getId() !== $device->getMerchantId())
        {
            throw new LogicException('Device does not belong to merchant in context');
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
    public function setHandle($handle)
    {
        if (($handle instanceof Handle\Entity) === false)
        {
            return;
        }

        if ($handle->isAllowedToMerchant($this->merchant->getId()) === false)
        {
            throw new LogicException('Merchant is not allowed to use the handle');
        }

        $this->handle = $handle;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $validator = new JitValidator();

        $validator->rules(self::OPTIONS_RULES)
                  ->caller($this)
                  ->setStrictFalse()
                  ->input($options)
                  ->validate();

        $this->options = $options;
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
        return ($this->getContextType(true) === self::APPLICATION);
    }

    /**
     * Check if context is Merchant
     *
     * @return bool
     * @throws BadRequestException
     */
    public function isContextMerchant(): bool
    {
        return ($this->getContextType(true) === self::MERCHANT);
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
     * Return handleId from the context
     *
     * @return string
     */
    public function handleId(): string
    {
        return $this->handle->getHandle();
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
