<?php

namespace RZP\Tests\P2p\Service\Base\Fixtures;

use RZP\Models;
use Hulk\Constants\Entity;
use Hulk\Models\Merchant\Account;
use Hulk\Models\Base\UniqueIdEntity;
use Illuminate\Database\Eloquent\Model;
use RZP\Tests\P2p\Service\Base\Constants;
use RZP\Tests\P2p\Service\Base\ExceptionTrait;

/**
 * Class Fixtures
 *
 * @property Models\Merchant\Entity $merchant
 * @property Models\Customer\Entity $customer
 * @property Models\Device\Entity $device
 * @property Models\BankAccount\Entity $bank_account
 * @property Models\Vpa\Entity $vpa
 *
 * @package RZP\Tests\P2p\Service\Base\Fixtures
 */
class Fixtures extends Constants
{
    use ExceptionTrait;

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
            'device'        => self::LOCAL_CUSTOMER_DEVICE,
            'customer'      => self::LOCAL_CUSTOMER,
            'bank_account'  => self::LOCAL_CUSTOMERL_BANK_ACCOUNT,
            'vpa'           => self::LOCAL_CUSTOMER_VPA,
        ],
        self::DEVICE_2 => [
            'merchant'      => self::TEST_MERCHANT,
            'device'        => self::LOCAL_CUSTOMER_2_DEVICE,
            'customer'      => self::LOCAL_CUSTOMER,
            'bank_account'  => self::LOCAL_CUSTOMER_2_BANK_ACCOUNT,
            'vpa'           => self::LOCAL_CUSTOMER_2_VPA,
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
            'merchant_id'        => '10000000000000',
            'global_customer_id' => 'TestGloblCstmr',
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
     * @return Models\Device\Entity
     */
    public function createDevice(
        array $attributes,
        bool $set = false): Models\Device\Entity
    {
        $defaults = [
            'merchant_id' => $this->current->customer->getMerchantId(),
            'customer_id' => $this->current->customer->getId(),
        ];

        $entity = factory(Models\Device\Entity::class)->create(array_merge($defaults, $attributes));

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
     * @return Models\BankAccount\Entity
     */
    public function createBankAccount(
        array $attributes,
        bool $set = false): Models\BankAccount\Entity
    {
        $defaults = [
            'merchant_id' => $this->current->customer->getMerchantId(),
            'owner_type'  => 'customer',
            'owner_id'    => $this->current->customer->getId(),
        ];

        $entity = factory(Models\BankAccount\Entity::class)->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->bank_account = $entity;
        }

        return $entity;
    }

    /**
     * Create and attach bank account beneficiary to customer
     *
     * @param array $attributes
     * @return Models\BankAccount\Entity
     */
    public function createBankAccountBeneficiary(array $attributes): Models\BankAccount\Entity
    {
        $override = [
            'merchant_id'    => Account::SHARED_ACCOUNT,
            'owner_type'     => null,
            'owner_id'       => null,
        ];

        $entity = factory(Models\BankAccount\Entity::class)->create(array_merge($attributes, $override));

        $this->current->customer->bank_account_beneficiaries()->attach($entity);

        return $entity;
    }

    /**
     * Create VPA for attributes for set customer
     *
     * @param array $attributes [must contain address and bank_account_id]
     * @param bool $set
     * @return Models\Vpa\Entity
     */
    public function createVpa(
        array $attributes,
        bool $set = false): Models\Vpa\Entity
    {
        $defaults = [
            'bank_account_id'   => $this->current->bank_account->getId(),
            'merchant_id'       => $this->current->customer->getMerchantId(),
            'owner_type'        => 'customer',
            'owner_id'          => $this->current->customer->getId(),
        ];

        $entity = factory(Models\Vpa\Entity::class)->create(array_merge($defaults, $attributes));

        if ($set === true)
        {
            $this->current->vpa = $entity;
        }

        return $entity;
    }

    /**
     * Create and attach vpa beneficiary to customer
     *
     * @param array $attributes
     * @return Models\Vpa\Entity
     */
    public function createVpaBeneficiary(array $attributes): Models\Vpa\Entity
    {
        $override = [
            'merchant_id'       => Account::SHARED_ACCOUNT,
            'owner_type'        => null,
            'owner_id'          => null,
        ];

        $entity = factory(Models\Vpa\Entity::class)->create(array_merge($attributes, $override));

        $this->current->customer->vpa_beneficiaries()->attach($entity);

        return $entity;
    }

    /**
     * Create P2p for Set Vpa as Sender and Vpa2 as receiver for attributes for set customer
     *
     * @param array $attributes
     * @param bool $set
     * @return Models\P2p\Entity
     */
    public function createP2p(
        array $attributes,
        bool $set = false): Models\P2p\Entity
    {
        $defaults = [
            'amount'            => 50000,
            'status'            => 'created',
            'type'              => 'push',
            'transaction_type'  => 'debit',
            'sender_id'         => $this->current->vpa->getId(),
            'sender_type'       => 'vpa',
            'receiver_id'       => $this->device(self::DEVICE_2)->vpa->getId(),
            'receiver_type'     => 'vpa',
            'bank_account_id'   => $this->current->bank_account->getId(),
            'merchant_id'       => $this->current->customer->getMerchantId(),
            'owner_type'        => 'customer',
            'owner_id'          => $this->current->customer->getId(),
        ];

        $entity = factory(Models\P2p\Entity::class)->create(array_merge($defaults, $attributes));

        return $entity;
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
