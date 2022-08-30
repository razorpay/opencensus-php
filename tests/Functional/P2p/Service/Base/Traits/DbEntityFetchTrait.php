<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use RZP\Models;
use RZP\Models\P2p;
use RZP\Constants\Entity;
use RZP\Tests\Functional\Helpers;
use RZP\Models\Base\PublicCollection;

trait DbEntityFetchTrait
{
    use Helpers\DbEntityFetchTrait;

    protected $dbEntityFetchMode = 'test';

    public function setDbEntityFetchModeToTest()
    {
        $this->dbEntityFetchMode = 'test';
    }

    public function setDbEntityFetchModeToLive()
    {
        $this->dbEntityFetchMode = 'live';
    }

    /********************************** MERCHANT **********************************/

    public function getDbMerchantById(string $id): Models\Merchant\Entity
    {
        return $this->getDbEntityById(Entity::MERCHANT, $id, $this->dbEntityFetchMode);
    }

    /********************************** CUSTOMER **********************************/

    public function getDbCustomerById(string $id): Models\Customer\Entity
    {
        return $this->getDbEntityById(Entity::CUSTOMER, $id, $this->dbEntityFetchMode);
    }

    /********************************** DEVICES ***********************************/

    public function getDbLastDevice(): P2p\Device\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_DEVICE, $this->dbEntityFetchMode);
    }

    public function getDbDeviceById(string $id): P2p\Device\Entity
    {
        return $this->getDbEntityById(Entity::P2P_DEVICE, $id, $this->dbEntityFetchMode);
    }

    public function getDbDevices(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_DEVICE, $where, $this->dbEntityFetchMode);
    }

    /********************************** BANK ACCOUNT ***********************************/

    public function getDbLastBankAccount(): P2p\BankAccount\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_BANK_ACCOUNT, $this->dbEntityFetchMode);
    }

    public function getDbBankAccountById(string $id): P2p\BankAccount\Entity
    {
        return $this->getDbEntityById(Entity::P2P_BANK_ACCOUNT, $id, $this->dbEntityFetchMode);
    }

    public function getDbBankAccounts(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_BANK_ACCOUNT, $where, $this->dbEntityFetchMode);
    }

    /*************************************** VPA ***************************************/

    public function getDbHandleById(string $id): P2p\Vpa\Handle\Entity
    {
        return $this->getEntityObjectForMode(Entity::P2P_HANDLE, $this->dbEntityFetchMode)
                    ->findOrFailPublic($id);
    }

    public function getDbLastVpa(): P2p\Vpa\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_VPA, $this->dbEntityFetchMode);
    }

    public function getDbVpaById(string $id): P2p\Vpa\Entity
    {
        return $this->getDbEntityById(Entity::P2P_VPA, $id, $this->dbEntityFetchMode);
    }

    public function getDbVpas(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_VPA, $where, $this->dbEntityFetchMode);
    }

    /*********************************** Transaction **********************************/

    public function getDbLastTransaction(): P2p\Transaction\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_TRANSACTION, $this->dbEntityFetchMode);
    }

    public function getDbTransactionById(string $id): P2p\Transaction\Entity
    {
        return $this->getDbEntityById(Entity::P2P_TRANSACTION, $id, $this->dbEntityFetchMode);
    }

    public function getDbTransactions(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_TRANSACTION, $where, $this->dbEntityFetchMode);
    }

    /******************************* Client ****************************************/
    public function getDbLastClient(): P2p\Client\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_CLIENT, $this->dbEntityFetchMode);
    }

    /*********************************** Mandate **********************************/

    public function getDbLastMandate(): P2p\Mandate\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_MANDATE, $this->dbEntityFetchMode);
    }

    public function getDbMandateById(string $id): P2p\Mandate\Entity
    {
        return $this->getDbEntityById(Entity::P2P_MANDATE, $id, $this->dbEntityFetchMode);
    }

    public function getDbMandates(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_MANDATE, $where, $this->dbEntityFetchMode);
    }

    /****************************** Blacklist *************************************/

    public function getDbLastBlacklist(): P2p\BlackList\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_BLACKLIST, $this->dbEntityFetchMode);
    }

    public function getDbBlacklists(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_BLACKLIST, $where, $this->dbEntityFetchMode);
    }

    /*********************************** Mandate Patch**********************************/

    public function getDbLastPatch(): P2p\Mandate\Patch\Entity
    {
        return $this->getDbLastEntity(Entity::P2P_MANDATE_PATCH, $this->dbEntityFetchMode);
    }

    public function getDbPatchById(string $id): P2p\Mandate\Patch\Entity
    {
        return $this->getDbEntityById(Entity::P2P_MANDATE_PATCH, $id, $this->dbEntityFetchMode);
    }

    public function getDbPatches(array $where): PublicCollection
    {
        return $this->getDbEntities(Entity::P2P_MANDATE_PATCH, $where, $this->dbEntityFetchMode);
    }
}
