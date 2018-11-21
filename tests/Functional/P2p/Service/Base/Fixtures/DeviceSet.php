<?php

namespace RZP\Tests\P2p\Service\Base\Fixtures;

use Hulk\Constants\DbConnection;
use Hulk\Models;
use Hulk\Constants\Entity;
use Hulk\Exception\RuntimeException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

/**
 * Device set stores a map of entity and it's id
 * It works on lazy loading principle will not
 * try to fetch entity until required.
 *
 * Technically device set can store any entity and id
 * But for now we are only supporting these listed.
 *
 * @property Models\Merchant\Entity $merchant
 * @property Models\Customer\Entity $customer
 * @property Models\Device\Entity $device
 * @property Models\BankAccount\Entity $bank_account
 * @property Models\Vpa\Entity $vpa
 *
 * Class DeviceSet
 * @package Tests\Concerns
 */
class DeviceSet
{
    use DbEntityFetchTrait;

    /**
     * The set is a map of entity and id which we use to resolve.
     * Now it can be empty if one wants to set these properties
     * dynamically, this way __get function will not get called
     * and set array will not be used. So in other words it works
     * both ways e.i. lazy loading with just and ids and setting
     * properties dynamically.
     *
     * @var array
     */
    private $set;

    public function __construct(array $set)
    {
        $this->set = $set;
    }

    public function __get(string $property)
    {
        switch ($property)
        {
            case 'merchant':
                $this->merchant = $this->getDbEntityById(Entity::MERCHANT, $this->set['merchant']);
                break;

            case 'customer':
                $this->customer = $this->getDbEntityById(Entity::CUSTOMER, $this->set['customer']);
                break;

            case 'device':
                $this->device = $this->getDbEntityById(Entity::DEVICE, $this->set['device']);
                break;

            case 'bank_account':
                $this->bank_account = $this->getDbEntityById(Entity::BANK_ACCOUNT, $this->set['bank_account']);
                break;

            case 'vpa':
                $this->vpa = $this->getDbEntityById(Entity::VPA, $this->set['vpa']);
                break;

            default:
                throw new RuntimeException("Invalid property for device set, $property");
        }

        return $this->$property;
    }

    public function __set(string $property, Models\Base\Entity $value)
    {
        switch ($property)
        {
            case 'merchant':
                $this->merchant = $value;
                break;

            case 'customer':
                $this->customer = $value;
                break;

            case 'device':
                $this->device = $value;
                break;

            case 'bank_account':
                $this->bank_account = $value;
                break;

            case 'vpa':
                $this->vpa = $value;
                break;

            default:
                throw new RuntimeException("Invalid property for device set, $property");
        }
    }
}
