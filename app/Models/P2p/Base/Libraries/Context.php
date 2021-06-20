<?php

namespace RZP\Models\P2p\Base\Libraries;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Client;
use RZP\Base\JitValidator;
use RZP\Error\P2p\ErrorCode;
use Illuminate\Http\Request;
use RZP\Http\BasicAuth\Type;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\P2p\Base\Traits;
use RZP\Trace\P2pTraceProcessor;
use RZP\Models\P2p\Base\MorphMap;
use RZP\Models\Base\UniqueIdEntity;

class Context extends ArrayObject
{
    use Traits\ExceptionTrait;

    const APPLICATION               = 'application';

    const MODE                      = 'mode';

    const MERCHANT                  = 'merchant';

    const DEVICE                    = 'device';

    const HANDLE                    = 'handle';

    const REQUEST_ID                = 'request_id';

    const GATEWAY                   = 'gateway';

    const NAME                      = 'name';

    const ACTION                    = 'action';

    const INPUT                     = 'input';


    const META                      = 'meta';

    /**** Meta Keys ******/
    const OS                        = 'os';

    const OS_VERSION                = 'os_version';

    const SDK_SESSION_ID            = 'sdk_session_id';

    const SDK_VERSION               = 'sdk_version';

    const NETWORK_TYPE              = 'network_type';

    const OPTIONS_RULES = [
        self::REQUEST_ID                              => 'nullable|string|max:50',
        self::HANDLE                                  => 'required|string',
        self::DEVICE                                  => 'array',
        self::DEVICE . '.' . Device\Entity::IP        => 'nullable|ipv4',
        self::DEVICE . '.' . Device\Entity::GEOCODE   => 'nullable|string|max:20',
        self::META                                    => 'array',
        self::META . '.' . self::OS                   => 'nullable|string|max:50',
        self::META . '.' . self::OS_VERSION           => 'nullable|string|max:50',
        self::META . '.' . self::SDK_SESSION_ID       => 'nullable|string|max:50',
        self::META . '.' . self::SDK_VERSION          => 'nullable|string|max:50',
        self::META . '.' . self::NETWORK_TYPE         => 'nullable|string|max:50',
        self::META . '.' . Device\Entity::IP          => 'nullable|ipv4',
        self::META . '.' . Device\Entity::GEOCODE     => 'nullable|string|max:20',
    ];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $mode;

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
     * @var boolean
     */
    protected $shouldRefreshDeviceToken;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $gatewayData = [];

    public function __construct()
    {
        $this->options = new ArrayBag();

        $this->setShouldRefreshDeviceToken(true);
    }

    public function loadWithRequest(Request $request)
    {
        $basicAuth = app('basicauth');

        // Merchant and handle are required over Public and Device Auth
        if (($basicAuth->getAuthType() === Type::PRIVATE_AUTH) or
            ($basicAuth->getAuthType() === Type::PUBLIC_AUTH) or
            ($basicAuth->getAuthType() === Type::DEVICE_AUTH))
        {
            // Setting the options first as options will be use to resolve the context
            $this->setOptions(ContextMap::resolveRequestHeaders($request));

            // Handle is set in context from the options, each HTTP request will have handle specified
            $this->setHandleAndMode($this->options[self::HANDLE], $basicAuth->getMode());

            // As the context is loaded from HTTP request, we are using the basic auth
            // for merchant and device, later we will have to change this if we change the auth.
            // We are only going to set the context entities if the are available in basic auth.
            if (($basicAuth->getMerchant() instanceof Merchant\Entity))
            {
                // Merchant must be in basic auth as the auth is either public or device
                $this->setMerchant($basicAuth->getMerchant());
            }
            else
            {
                throw $this->logicException(ErrorCode::SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED);
            }
        }

        // Device and Device Token are required over Device Auth
        if ($basicAuth->getAuthType() === Type::DEVICE_AUTH)
        {
            if ($basicAuth->getDevice() instanceof Device\Entity)
            {
                $this->setDevice($basicAuth->getDevice());

                $this->validateDeviceTokenForRequest($request);
            }
            else
            {
                throw $this->logicException(ErrorCode::SERVER_ERROR_CONTEXT_DEVICE_REQUIRED);
            }
        }

        // For Direct auth application needs to set
        if ($basicAuth->getAuthType() === Type::DIRECT_AUTH)
        {
            $this->type = self::APPLICATION;
        }

        // For internal service calls
        if($basicAuth->getAuthType() === Type::PRIVILEGE_AUTH)
        {
            $this->type = self::APPLICATION;
        }

        // Note:: We are not putting application as instance variable
        // to ensure that context is independent of application container.
        $this->registerServices();
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
        $client = $this->handle->client(Client\Type::MERCHANT, $merchant->getId());

        if (($client instanceof Client\Entity) === false)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NOT_ALLOWED_ON_HANDLE);
        }

        $this->type = self::MERCHANT;

        $this->merchant = $merchant;

        $this->handle->setClient($client);
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
    public function setDevice(Device\Entity $device, bool $skipToken = false)
    {
        // Basic auth already takes care of device owner, here we are only enforcing it.
        if ($this->merchant->getId() !== $device->getMerchantId())
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_DEVICE_DOES_NOT_BELONG_TO_MERCHANT);
        }

        if ($skipToken === false)
        {
            $deviceToken = $device->deviceTokens()
                ->handle($this->handle)
                ->verified()->latest()->first();

            // If there is no device token found, the context will fail
            if (($deviceToken instanceof Device\DeviceToken\Entity) === false)
            {
                throw $this->badRequestException(ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE);
            }

            // Setting the device token with the device
            $this->setDeviceToken($deviceToken);
        }

        $this->type = self::DEVICE;

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
     * @return Client\Entity
     */
    public function getClient()
    {
        return $this->handle->getClient();
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode(string $mode)
    {
        \Database\DefaultConnection::set($mode);

        // Just to keep things rolling in API Example: Event Handling
        app()->instance('rzp.mode', $mode);

        app()->get('basicauth')->setMode($mode);

        $this->mode = $mode;
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
     */
    public function getContextType()
    {
        return $this->type;
    }

    /**
     * Check if context is Application
     *
     * @return bool
     */
    public function isContextApplication(): bool
    {
        return ($this->getContextType(true) === self::APPLICATION) or ($this->isContextMerchant());
    }

    /**
     * Check if context is Merchant
     *
     * @return bool
     */
    public function isContextMerchant(): bool
    {
        return ($this->getContextType(true) === self::MERCHANT) or ($this->isContextDevice());
    }

    /**
     * Check if context is Device
     *
     * @return bool
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

    /**
     * Return UPI Prefix in Handle from the context
     *
     * @return string
     */
    public function handlePrefix(): string
    {
        return $this->handle->getTxnPrefix($this->getMerchant()->getId());
    }

    /**
     * Request id will be set in options at the time of loading
     *
     * @return string
     */
    public function getRequestId(): string
    {
        $requestId = $this->getOptions()->get(self::REQUEST_ID);

        if (empty($requestId) === true)
        {
            $this->options->put(self::REQUEST_ID, UniqueIdEntity::generateUniqueId());
        }

        return $this->getOptions()->get(self::REQUEST_ID);
    }

    /**
     * Validates whether the given merchant is in context
     *
     * @param Merchant\Entity $merchant
     */
    public function validateMerchant(Merchant\Entity $merchant, string $code)
    {
        if ($this->merchant->getId() !== $merchant->getId())
        {
            throw $this->badRequestException($code);
        }
    }

    /**
     * Normally these services are registered from Providers, but in case
     * of P2P, these services may lead to conflicts. Thus only be called for P2P.
     */
    public function registerServices()
    {
        // Morphing must only be handled within P2P requests
        MorphMap::boot();

        // We only want to register the P2P Trace Processor within P2P requests
        app('trace')->pushNamedProcessor(new P2pTraceProcessor($this));
    }

    /**
     * @param string $handleCode
     * @param string|null $mode
     * @throws \RZP\Exception\P2p\BadRequestException
     */
    public function setHandleAndMode(string $handleCode, string $mode = null)
    {
        $modes = [Mode::LIVE, Mode::TEST];

        // If mode is passed, we will only look for that mode
        if (is_null($mode) === false)
        {
            $modes = [$mode];
        }

        foreach ($modes as $mode)
        {
            $handle = app('repo')->p2p_handle->connection($mode)->find($handleCode);

            if (($handle instanceof Handle\Entity) and
                ($handle->isActive() === true))
            {
                $this->setMode($mode);

                $this->setHandle($handle);

                return;
            }
        }

        throw $this->badRequestException(ErrorCode::BAD_REQUEST_INVALID_HANDLE);
    }

    public function setShouldRefreshDeviceToken(bool $value)
    {
        $this->shouldRefreshDeviceToken = $value;
    }

    protected function validateDeviceTokenForRequest(Request $request)
    {
        // If device token is present
        if ($this->getDeviceToken() instanceof Device\DeviceToken\Entity)
        {
            if (in_array($request->route()->getName(), ContextMap::SKIP_TOKEN_VALIDATION_ROUTES, true) === false)
            {
                if ($this->getDeviceToken()->shouldRefresh() and $this->shouldRefreshDeviceToken)
                {
                    throw $this->badRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
                }
            }
        }
    }
}
