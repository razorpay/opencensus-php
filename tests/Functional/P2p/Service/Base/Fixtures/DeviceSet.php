<?php

namespace RZP\Tests\P2p\Service\Base\Fixtures;

use RZP\Models;
use RZP\Models\P2p;
use RZP\Constants\Entity;
use RZP\Tests\P2p\Service\Base\Traits;

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
 * @property P2p\Device\Entity $device
 * @property P2p\Vpa\Handle\Entity $handle
 * @property P2p\BankAccount\Entity $bank_account
 * @property P2p\Vpa\Entity $vpa
 * @property P2p\Client\Entity $client
 *
 * Class DeviceSet
 * @package Tests\Concerns
 */
class DeviceSet
{
    use Traits\ExceptionTrait;
    use Traits\DbEntityFetchTrait;

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
                $this->merchant = $this->getDbMerchantById($this->set['merchant']);
                break;

            case 'customer':
                $this->customer = $this->getDbCustomerById($this->set['customer']);
                break;

            case 'device':
                $this->device = $this->getDbDeviceById($this->set['device']);
                break;

            case 'handle':
                $this->handle = $this->getDbHandleById($this->set['handle']);
                break;

            case 'bank_account':
                $this->bank_account = $this->getDbBankAccountById($this->set['bank_account']);
                break;

            case 'client':
                $this->client = $this->device->client($this->handle);
                break;

            case 'vpa':
                $this->vpa = $this->getDbVpaById($this->set['vpa']);
                break;

            default:
                $this->throwTestingException('Invalid property for device set', [$property]);
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

            case 'handle':
                $this->handle = $value;
                break;

            case 'bank_account':
                $this->bank_account = $value;
                break;

            case 'vpa':
                $this->vpa = $value;
                break;

            case 'client':
                $this->client = $value;
                break;

            default:
                $this->throwTestingException('Invalid property for device set', [$property]);
        }
    }
}
