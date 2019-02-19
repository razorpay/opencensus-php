<?php

namespace RZP\Tests\P2p\Service\Base\Fixtures;

use RZP\Models;
use RZP\Models\P2p;
use Hulk\Constants\Entity;
use Hulk\Models\Merchant\Account;
use Hulk\Models\Base\UniqueIdEntity;
use RZP\Tests\P2p\Service\Base\Traits;
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
    protected $deviceSetMap = [
        self::DEVICE_1 => [
            'merchant'      => self::TEST_MERCHANT,
            'customer'      => self::RZP_LOCAL_CUSTOMER_1,
            'device'        => self::CUSTOMER_1_DEVICE_1,
            'handle'        => self::RAZOR_SHARP,
            'bank_account'  => self::CUSTOMER_1_BANK_ACCOUNT_1,
            'vpa'           => self::CUSTOMER_1_VPA_1,
        ],
        self::DEVICE_2 => [
            'merchant'      => self::TEST_MERCHANT,
            'customer'      => self::RZP_LOCAL_CUSTOMER_2,
            'device'        => self::CUSTOMER_2_DEVICE_1,
            'handle'        => self::RAZOR_SHARP,
            'bank_account'  => self::CUSTOMER_2_BANK_ACCOUNT_1,
            'vpa'           => self::CUSTOMER_2_VPA_1,
        ],

    ];

    public function __construct()
    {
        $this->current = $this->device(self::DEVICE_1);
    }

    public function switchDevice(string $deviceSetId)
    {
        $this->current = $this->device($deviceSetId);
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
    public function device(string $deviceSetId, array $create = []): DeviceSet
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

        $entity = factory(Models\Customer\Entity::class)->create(array_merge($defaults, $attributes));

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

        $entity = factory(P2p\Device\Entity::class)->create(array_merge($defaults, $attributes));

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
        ];

        $entity = factory(P2p\BankAccount\Entity::class)->create(array_merge($defaults, $attributes));

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
        ];

        $entity = factory(P2p\Vpa\Entity::class)->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->vpa = $entity;
        }

        return $entity;
    }

    /**
     * @param bool $verified
     * @return P2p\Device\DeviceToken\Entity
     */
    public function currentDeviceToken(bool $verified = false)
    {
        $deviceToken = $this->device->deviceTokens()->handle($this->handle);

        if ($verified === true)
        {
            $deviceToken->verified();
        }

        return $deviceToken->first();
    }

    public function __get($property)
    {
        if ($this->current->{$property} !== null)
        {
            return $this->current->{$property};
        }

        $this->throwTestingException('Property not found in device set', [$property]);
    }
}
