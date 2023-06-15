<?php

namespace RZP\Tests\P2p\Service\Base\Fixtures;

use RZP\Models;
use RZP\Models\P2p;
use Hulk\Constants\Entity;
use Hulk\Models\Merchant\Account;
use Hulk\Models\Base\UniqueIdEntity;
use RZP\Tests\P2p\Service\Base\Traits;
use RZP\Tests\Functional\Fixtures\Fixtures as BaseFixtures;
use RZP\Tests\P2p\Service\Base\Constants;

/**
 * Class Fixtures
 *
 * @property Models\Merchant\Entity $merchant
 * @property Models\Customer\Entity $customer
 * @property P2p\Device\Entity $device
 * @property P2p\Vpa\Handle\Entity $handle
 * @property P2p\BankAccount\Entity $bank_account
 * @property P2p\Vpa\Entity $vpa
 * @property P2p\Client\Entity $client
 */
class Fixtures extends Constants
{
    use Traits\ExceptionTrait;
    use Traits\DbEntityFetchTrait;

    /**
     * A device set is a collection of entities which are linked together
     * and are used together very frequently. By default DEVICE_1 is set
     * as current device set, one can always switch between devices to
     * create end to end test cases where more that 1 account is required.
     *
     * @var DeviceSet
     */
    protected $current;

    /**
     * Caches all the device sets
     * @var array
     */
    protected $devices = [];

    /**
     * These are pre defined device maps, one can create and add more
     * device sets if required for extending test cases.
     *
     * @var array
     */
    protected $deviceSetMap = [];

    public function __construct(array $deviceSetMap = [])
    {
        $this->deviceSetMap = $deviceSetMap;

        $this->current = $this->deviceSet(self::DEVICE_1);
    }

    public function switchDeviceSet(string $deviceSetId)
    {
        $this->current = $this->deviceSet($deviceSetId);

        return $this;
    }

    /**
     * Will check if device set is cached, will return that otherwise
     * will try to create for predefined maps, if pre-defined map not
     * for it will try to create for new set with deviceSetId as given.
     *
     * @param string $deviceSetId
     * @param array $create
     * @return DeviceSet
     * @throws RuntimeException
     */
    public function deviceSet(string $deviceSetId, array $create = []): DeviceSet
    {
        if (empty($this->deviceSetMap[$deviceSetId]) === true)
        {
            if (empty($create) === true)
            {
                $this->throwTestingException('Invalid device set call', [$deviceSetId]);
            }

            $this->devices[$deviceSetId] = new DeviceSet($create);
        }

        if (isset($this->devices[$deviceSetId]) === false)
        {
            $this->devices[$deviceSetId] = new DeviceSet($this->deviceSetMap[$deviceSetId]);
        }

        return $this->devices[$deviceSetId];
    }

    /**
     * @param string $deviceSetId
     * @return Models\Merchant\Entity
     * @throws RuntimeException
     */
    public function merchant(string $deviceSetId): Models\Merchant\Entity
    {
        return $this->deviceSet($deviceSetId)->merchant;
    }

    /**
     * @param string $deviceSetId
     * @return Models\Customer\Entity
     * @throws RuntimeException
     */
    public function customer(string $deviceSetId): Models\Customer\Entity
    {
        return $this->deviceSet($deviceSetId)->customer;
    }

    /**
     * @param string $deviceSetId
     * @return P2p\Device\Entity
     * @throws RuntimeException
     */
    public function device(string $deviceSetId): P2p\Device\Entity
    {
        return $this->deviceSet($deviceSetId)->device;
    }

    /**
     * @param string $deviceSetId
     * @return P2p\Device\DeviceToken\Entity
     * @throws RuntimeException
     */
    public function deviceToken(string $deviceSetId, bool $verified = true): P2p\Device\DeviceToken\Entity
    {
        $tokens = $this->deviceSet($deviceSetId)->device->deviceTokens()->handle($this->handle);

        if ($verified === true)
        {
            $tokens->verified();
        }

        return $tokens->first();
    }

    /**
     * @param string $deviceSetId
     * @return P2p\Vpa\Handle\Entity
     * @throws RuntimeException
     */
    public function handle(string $deviceSetId): P2p\Vpa\Handle\Entity
    {
        return $this->deviceSet($deviceSetId)->handle;
    }

    /**
     * @param string $deviceSetId
     * @return P2p\BankAccount\Entity
     * @throws RuntimeException
     */
    public function bankAccount(string $deviceSetId): P2p\BankAccount\Entity
    {
        return $this->deviceSet($deviceSetId)->bank_account;
    }

    /**
     * @param string $deviceSetId
     * @return P2p\Vpa\Entity
     * @throws RuntimeException
     */
    public function vpa(string $deviceSetId): P2p\Vpa\Entity
    {
        return $this->deviceSet($deviceSetId)->vpa;
    }

    /**
     * @param string
     * @return P2p\Client\Entity
     * @throws RuntimeException
     */
    public function client(string $deviceSetId): P2p\Client\Entity
    {
        return $this->deviceSet($deviceSetId)->client;
    }

    /**
     * Create customer for passed attributes
     *
     * @param array $attributes
     * @param bool $set
     * @return Models\Customer\Entity
     */
    public function createCustomer(
        array $attributes,
        bool $set = false): Models\Customer\Entity
    {
        $defaults = [
            Models\Customer\Entity::MERCHANT_ID => $this->merchant->getId(),
        ];

        $entity = Models\Customer\Entity::factory()->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->customer = $entity;
        }

        return $entity;
    }

    /**
     * Create device for attributes for set customer
     *
     * @param array $attributes
     * @param bool $set
     * @return P2p\Device\Entity
     */
    public function createDevice(
        array $attributes,
        bool $set = false): P2p\Device\Entity
    {
        $defaults = [
            P2p\Device\Entity::MERCHANT_ID => $this->current->customer->getMerchantId(),
            P2p\Device\Entity::CUSTOMER_ID => $this->current->customer->getId(),
        ];

        $entity = P2p\Device\Entity::factory()->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->device = $entity;
        }

        return $entity;
    }

    /**
     * Create bank account for attributes for set customer
     *
     * @param array $attributes
     * @param bool $set
     * @return P2p\BankAccount\Entity
     */
    public function createBankAccount(
        array $attributes,
        bool $set = false): P2p\BankAccount\Entity
    {
        $defaults = [
            P2p\BankAccount\Entity::DEVICE_ID    => $this->current->device->getId(),
            P2p\BankAccount\Entity::HANDLE       => $this->current->handle->getCode(),
        ];

        $entity = P2p\BankAccount\Entity::factory()->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->bank_account = $entity;
        }

        return $entity;
    }

    /**
     * Create VPA for attributes for set customer
     *
     * @param array $attributes [must contain address and bank_account_id]
     * @param bool $set
     * @return P2p\Vpa\Entity
     */
    public function createVpa(
        array $attributes,
        bool $set = false): P2p\Vpa\Entity
    {
        $defaults = [
            P2p\Vpa\Entity::BANK_ACCOUNT_ID => $this->current->bank_account->getId(),
            P2p\Vpa\Entity::DEVICE_ID       => $this->current->device->getId(),
            P2p\BankAccount\Entity::HANDLE  => $this->current->handle->getCode(),
        ];

        $entity = P2p\Vpa\Entity::factory()->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->vpa = $entity;
        }

        return $entity;
    }

    public function createBeneficiary(array $attributes)
    {
        $defaults = [
            P2p\Beneficiary\Entity::DEVICE_ID           => $this->current->device->getId(),
            P2p\Beneficiary\Entity::ENTITY_TYPE         => P2p\Vpa\Entity::VPA,
            P2p\Beneficiary\Entity::ENTITY_ID           => $this->vpa(self::DEVICE_2)->getId(),
            P2p\Beneficiary\Entity::NAME                => P2p\Vpa\Entity::VPA,
        ];

        $beneficiary = new P2p\Beneficiary\Entity();

        $beneficiary->forceFill(array_merge($defaults, $attributes))->saveOrFail();

        return $beneficiary;
    }

    public function createRegisterToken(array $attributes): P2p\Device\RegisterToken\Entity
    {
        $defaults = [
            P2p\Device\Entity::CUSTOMER_ID      => $this->customer->getPublicId(),
            P2p\Device\Entity::IP               => '179.0.0.1',
            P2p\Device\Entity::OS               => 'android',
            P2p\Device\Entity::OS_VERSION       => '5.0.1',
            P2p\Device\Entity::SIMID            => '0',
            P2p\Device\Entity::UUID             => '5637293534543',
            P2p\Device\Entity::TYPE             => 'mobile',
            P2p\Device\Entity::GEOCODE          => '12.971599,77.594566',
            P2p\Device\Entity::APP_NAME         => 'com.razorpay',
        ];

        $create[P2p\Device\RegisterToken\Entity::DEVICE_DATA] = array_merge($defaults, $attributes);

        $entity = P2p\Device\RegisterToken\Entity::factory()->create($create);

        return $entity;
    }

    public function disableHandle(string $code, string $mode = 'live')
    {
        $this->getDbEntityById('p2p_handle', $code)
             ->setConnection($mode)
             ->setActive(false)->saveOrFail();
    }

    public function __get($property)
    {
        if ($this->current->{$property} !== null)
        {
            return $this->current->{$property};
        }

        $this->throwTestingException('Property not found in device set', [$property]);
    }

    public function enableFeatures($featureName)
    {
        $BaseFixtures = new BaseFixtures();

        $attributes = [
            'name'      => $featureName,
            'entity_id' => $this->merchant->getId(),
            'entity_type' => 'merchant'
        ];

        $BaseFixtures->create('feature',$attributes);
    }
}
