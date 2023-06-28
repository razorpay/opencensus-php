<?php

namespace RZP\Models\Address;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\traits\AsvFetch;
use RZP\Models\Merchant\Acs\traits\AsvFetchCommon;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;
use RZP\Modules\Acs\Wrapper\MerchantStakeholder as MerchantStakeholderWrapper;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder as StakeholderSDKWrapper;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;


class Repository extends Base\Repository
{

    use AsvFetchCommon;
    protected $entity = 'address';

    public AsvRouter $asvRouter;

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }


    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_ID   => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:32',
        Entity::TYPE        => 'sometimes|string|max:32',
        Entity::STATE       => 'sometimes|string|max:64',
        Entity::COUNTRY     => 'sometimes|string|max:64',
    ];

    public function fetchCurrentPrimaryAddressOfEntity(Base\Entity $entity, Entity $address)
    {
        $currentPrimaryAddresses = $this->newQuery()
                                        ->where(Entity::ENTITY_ID, '=', $entity->getId())
                                        ->where(Entity::TYPE, '=', $address->getType())
                                        ->where(Entity::PRIMARY, '=', '1')
                                        ->get();

        return $currentPrimaryAddresses;
    }

    public function fetchPrimaryAddressOfEntityOfType(Base\Entity $entity, $type)
    {
        $primaryAddressOfType = $this->newQuery()
                                     ->where(Entity::ENTITY_ID, '=', $entity->getId())
                                     ->where(Entity::TYPE, '=', $type)
                                     ->where(Entity::PRIMARY, '=', 1)
                                     ->first();

        return $primaryAddressOfType;
    }

    public function fetchPrimaryAddressOfEntityOfTypeCallBack(Base\Entity $entity, $type) {
        return function() use ($entity, $type) {
            return $this->fetchPrimaryAddressOfEntityOfType($entity, $type);
        };
    }

    public function fetchPrimaryAddressForStakeholderOfTypeResidential(Base\Entity $stakeholder, $type) {
        return $this->getEntityDetails(
            ASVV2Constant::GET_PRIMARY_ADDRESS_FOR_STAKEHOLDER,
            $this->asvRouter->shouldRouteToAccountService($stakeholder->getId(), get_class($this), FunctionConstant::GET_BY_STAKEHOLDER_ID),
            (new StakeholderSDKWrapper())->getAddressForStakeholderIgnoreInvalidArgumentCallBack($stakeholder->getId()),
            $this->fetchPrimaryAddressOfEntityOfTypeCallBack($stakeholder, $type)
        );
    }

    public function findByEntityAndId($addressId, Base\Entity $entity)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entity->getId())
                    ->findOrFail($addressId);
    }

    /**
     * Gets the latest address. If $except parameter is sent as true,
     * we exclude that address while fetching the latest address.
     *
     * @param Base\Entity $entity
     * @param Entity $address
     * @param null|boolean $except
     * @return Entity
     */
    public function fetchLatestAddressForEntity(Base\Entity $entity, Entity $address, $except = false)
    {
        // NOTE: except works on a collection and not on an entity.

        $latestAddress = $this->newQuery()
                              ->where(Entity::ENTITY_ID, '=', $entity->getId())
                              ->where(Entity::TYPE, '=', $address->getType())
                              ->latest()
                              ->get();

        if ($except === true)
        {
            return $latestAddress->except($address->getId())->first();
        }
        else
        {
            return $latestAddress->first();
        }
    }

    public function fetchAddressesForEntity(Base\Entity $entity, array $input)
    {
        $addresses = $this->newQuery()
                          ->where(Entity::ENTITY_ID, '=', $entity->getId());

        if (empty($input[Entity::TYPE]) === false)
        {
            $type = $input[Entity::TYPE];
            $addresses = $addresses->where(Entity::TYPE, '=', $type);
        }

        return $addresses->get();
    }

    public function fetchRzpAddressesFor1cc(Base\Entity $entity)
    {
        $addresses = $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->where(function ($query)
            {
                $query->whereIn(Entity::SOURCE_TYPE, ['bulk_upload', 'shopify', 'woocommerce'])
                    ->orWhereNull(Entity::SOURCE_TYPE);
            });
        return $addresses->get();
    }

    public function fetchThirdPartyAddressesFor1cc(Base\Entity $entity)
    {
        $addresses = $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->whereIn(Entity::SOURCE_TYPE, ['thirdwatch', 'payment_pages']);

        return $addresses->get();
    }

    public function fetchRzpAddressCountFor1cc(Base\Entity $entity)
    {
        $query = $this->newQuery();
        return $query->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->where(function ($query)
            {
                $query->whereIn(Entity::SOURCE_TYPE, ['bulk_upload', 'shopify', 'woocommerce'])
                    ->orWhereNull(Entity::SOURCE_TYPE);
            })
            ->count();
    }

    public function fetchThirdPartyAddressCountFor1cc(Base\Entity $entity)
    {
        $sourceTypes = ['thirdwatch', 'payment_pages'];

        return $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->whereIn(Entity::SOURCE_TYPE, $sourceTypes)
            ->count();
    }

    /**
     * @param string      $id
     * @param Base\Entity $entity
     * @param string|null $type
     *
     * @return Entity
     */
    public function findByPublicIdEntityAndTypeOrFail(
        string $id,
        Base\Entity $entity,
        string $type = null): Entity
    {
        Entity::verifyIdAndStripSign($id);

        $query = $this->newQuery()
                      ->where(Entity::ID, $id)
                      ->where(Entity::ENTITY_ID, $entity->getId())
                      ->where(Entity::ENTITY_TYPE, $entity->getEntity());

        if ($type !== null)
        {
            $query->where(Entity::TYPE, $type);
        }

        return $query->firstOrFailPublic();
    }

    public function fetchAddressesForContact(string $contact)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);

        return $this->newQuery()
                    ->selectRaw(Table::ADDRESS . '.*')
                    ->where($contactCol,$contact)
                    ->get();
    }

    /**
     *
     * Important: This function is for migration reads to account service.
     * Can be used to fetch residential address of stakeholder from Account Service
     * @param MerchantStakeholderEntity $entity
     * @param $type
     * @throws \Throwable
     */
    public function __fetchPrimaryAddressForStakeholder(MerchantStakeholderEntity $entity, $type)
    {
        $primaryAddressOfType = $this->fetchPrimaryAddressOfEntityOfType($entity, $type);
        if ($primaryAddressOfType === null) {
            return $primaryAddressOfType;
        }
        return (new MerchantStakeholderWrapper())->processFetchPrimaryResidentialAddressForStakeholder($entity, $primaryAddressOfType);
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used for saving the stakeholder address
     * @param MerchantStakeholderEntity $stakeholderEntity
     * @param Entity $addressEntity
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantStakeholderEntity $stakeholderEntity, AddressEntity $addressEntity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($stakeholderEntity, $addressEntity) {
            $this->repo->saveOrFail($addressEntity);
            (new MerchantStakeholderWrapper())->SaveOrFailAddress($stakeholderEntity, $addressEntity);
        });
    }

    public function fetchByEntityIds($entityIds, $entityType)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->whereIn(Entity::ENTITY_ID,  $entityIds)
            ->get();
    }
}
