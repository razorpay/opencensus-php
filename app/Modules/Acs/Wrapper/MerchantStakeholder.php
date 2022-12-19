<?php

namespace RZP\Modules\Acs\Wrapper;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Comparator\AddressComparator;
use RZP\Modules\Acs\Comparator\StakeholderComparator;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Acs\AsvClient;

class MerchantStakeholder extends Base
{
    public $saveApiHelper;
    private $stakeholderAsvClient;
    private $stakeholderComparator;
    private $addressComparator;
    private $stakeholderEntity = "stakeholder";
    private $addressEntity = "address";


    function __construct()
    {
        parent::__construct();
        $this->saveApiHelper = new SaveApiHelper();
        $this->stakeholderAsvClient = new AsvClient\StakeholderAsvClient();
        $this->stakeholderComparator = new StakeholderComparator();
        $this->addressComparator = new AddressComparator();
    }

    public function setStakeholderAsvClient($stakeholderAsvClient) {
        $this->stakeholderAsvClient = $stakeholderAsvClient;
    }

    public function getStakeholderAsvClient($stakeholderAsvClient) {
       return $this->stakeholderAsvClient;
    }

    /**
     * @param MerchantStakeholderEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantStakeholderEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    /**
     * @param MerchantStakeholderEntity $stakeholderEntity
     * @param AddressEntity $addressEntity
     * @throws \Google\ApiCore\ValidationException
     * @throws \RZP\Exception\IntegrationException
     */
    public function SaveOrFailAddress(MerchantStakeholderEntity $stakeholderEntity, AddressEntity $addressEntity)
    {
        $entityArray = $stakeholderEntity->toArray();
        $addressEntityArray = $addressEntity->toArray();
        $entityArray['addresses']['residential'] = $addressEntityArray;
        $this->saveApiHelper->saveOrFail($stakeholderEntity->getMerchantId(), $stakeholderEntity->getEntityName(), $addressEntity->getEntityName(), $entityArray);
    }


    /**
     * @throws Throwable
     * @throws IntegrationException
     */
    public function processFetchStakeholdersByMerchantId(string $merchantId, $apiStakeholderEntities) {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadowFetchStakeholdersByMerchantId($merchantId, $apiStakeholderEntities);
            return $apiStakeholderEntities;
        }
        else if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReverseShadowFetchStakeholdersByMerchantId($merchantId, $apiStakeholderEntities);
        }

        return $apiStakeholderEntities;
    }


    private function processReadShadowFetchStakeholdersByMerchantId(string $merchantId, $apiStakeholderEntities)
    {
        try{
            $this->fetchStakeholderByMerchantId($merchantId,$apiStakeholderEntities);
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "merchant_id" => $merchantId,
                    "entity" => $this->stakeholderEntity,
                ]);
        }
    }

    /**
     * @throws Throwable
     * @throws IntegrationException
     */
    private function processReverseShadowFetchStakeholdersByMerchantId(string $merchantId, $apiStakeholderEntities): PublicCollection
    {
        try{
            $asvStakeholderEntities =  $this->fetchStakeholderByMerchantId($merchantId,$apiStakeholderEntities);
            return ASVEntityMapper::OverwriteWithAsvEntities(
                StakeholderEntity::class,
                'id',
                ASVEntityMapper::EntitiesToArrayWithRawValues($apiStakeholderEntities),
                ASVEntityMapper::EntitiesToArrayWithRawValues($asvStakeholderEntities)
            );
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::ASV_REVERSE_SHADOW_EXCEPTION,
                [
                    "merchant_id" => $merchantId,
                    "entity" => $this->stakeholderEntity,
                ]);

            throw $ex;
        }
    }

    /**
     * @param string $merchantId
     * @param $apiStakeholderEntities
     * @throws IntegrationException
     */
    private function fetchStakeholderByMerchantId(string $merchantId, $apiStakeholderEntities)
    {
        $asvStakeholders = $this->stakeholderAsvClient->fetchStakeholderByMerchantId($merchantId);
        $asvStakeholderEntities = ASVEntityMapper::MapProtoObjectIteratorToEntityCollection(
            $asvStakeholders->getStakeholders(),
            StakeholderEntity::class);
        $difference = $this->stakeholderComparator->getDifferenceCompareByUniqueId($apiStakeholderEntities->toArray(),
            $asvStakeholderEntities->toArray(),
            'id'
        );
        $this->logDifferenceIfNotNilAndPushMetrics($this->stakeholderEntity, $difference,"", $merchantId);
        return $asvStakeholderEntities;
    }

    /**
     * @throws Throwable
     * @throws IntegrationException
     */
    public function processFetchStakeholderById(string $id, $apiStakeholderEntity) {
        if ($this->isShadowOrReverseShadowOnForOperation($apiStakeholderEntity->getMerchantId(), CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadowFetchStakeholdersById($id, $apiStakeholderEntity);
            return $apiStakeholderEntity;
        }
        else if ($this->isShadowOrReverseShadowOnForOperation($apiStakeholderEntity->getMerchantId(), CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReverseShadowFetchStakeholdersById($id, $apiStakeholderEntity);
        }

        return $apiStakeholderEntity;
    }

    private function processReadShadowFetchStakeholdersById(string $id, $apiStakeholderEntity): void
    {
        try{
            $this->fetchStakeholderByIdAndCompare($id,$apiStakeholderEntity);
            return;
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $id,
                    "entity" => $this->stakeholderEntity,
                ]);
        }

    }

    /**
     * @throws Throwable
     * @throws IntegrationException
     */
    private function processReverseShadowFetchStakeholdersById(string $id, $apiStakeholderEntity)
    {
        try{
            $asvStakeholderEntity = $this->fetchStakeholderByIdAndCompare($id, $apiStakeholderEntity);
            return ASVEntityMapper::OverwriteWithAsvEntity(
                ASVEntityMapper::EntityToArrayWithRawValues($apiStakeholderEntity),
                ASVEntityMapper::EntityToArrayWithRawValues($asvStakeholderEntity),
                StakeholderEntity::class);
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $id,
                    "entity" => $this->stakeholderEntity,
                ]);

            throw $ex;
        }
    }

    /**
     * @param string $id
     * @param $apiStakeholderEntity
     * @return mixed
     * @throws IntegrationException
     */
    public function fetchStakeholderByIdAndCompare(string $id, $apiStakeholderEntity): StakeholderEntity
    {
        $stakeholder = $this->stakeholderAsvClient->fetchStakeholderById($id)->getStakeholders()[0];
        $asvStakeholderEntity = ASVEntityMapper::MapProtoObjectToEntity($stakeholder, StakeholderEntity::class);
        $difference = $this->stakeholderComparator->getDifference($asvStakeholderEntity->toArray(), $apiStakeholderEntity->toArray());
        $this->logDifferenceIfNotNilAndPushMetrics($this->stakeholderEntity,$difference,$id, "");
        return $asvStakeholderEntity;
    }


    /**
     * @throws Throwable
     * @throws IntegrationException
     */
    public function processFetchPrimaryResidentialAddressForStakeholder(StakeholderEntity $stakeholder, $apiAddressEntity) {
        if ($this->isShadowOrReverseShadowOnForOperation($stakeholder->getMerchantId(), CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadowFetchAddressById($stakeholder->getId(), $apiAddressEntity);
            return $apiAddressEntity;
        }
        else if ($this->isShadowOrReverseShadowOnForOperation($stakeholder->getMerchantId(), CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReverseShadowFetchAddressById($stakeholder->getId(), $apiAddressEntity);
        }

        return $apiAddressEntity;
    }

    private function processReadShadowFetchAddressById(string $stakeholderId, $apiAddressEntity)
    {
        try{
            $this->fetchAddressForStakeholderAndCompare($stakeholderId, $apiAddressEntity);
            return $apiAddressEntity;
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $stakeholderId,
                    "entity" => $this->addressEntity,
                ]);
        }

        return $apiAddressEntity;
    }

    /**
     * @throws Throwable
     * @throws IntegrationException
     */
    private function processReverseShadowFetchAddressById(string $stakeholderId, $apiAddressEntity)
    {
        try{
            $asvAddressEntity = $this->fetchAddressForStakeholderAndCompare($stakeholderId, $apiAddressEntity);
            return ASVEntityMapper::OverwriteWithAsvEntity($apiAddressEntity->toArray(),$asvAddressEntity->toArray(), AddressEntity::class);
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $stakeholderId,
                    "entity" => $this->addressEntity,
                ]);

            throw $ex;
        }
    }

    /**
     * @throws IntegrationException
     */
    private function fetchAddressForStakeholderAndCompare(string $stakeholderId, $apiAddressEntity) {
        $asvAddress =  $this->stakeholderAsvClient->fetchAddressForStakeholder($stakeholderId);
        if (count($asvAddress->getAddresses()) == 0) {
            throw new IntegrationException(sprintf("no addresses found in asv for stakeholder id: %s", $stakeholderId));
        }
        $asvAddressEntity = ASVEntityMapper::MapProtoObjectToEntity($asvAddress->getAddresses()[0], AddressEntity::class);
        $difference = $this->addressComparator->getDifference($apiAddressEntity->toArray(), $asvAddressEntity->toArray());
        $this->logDifferenceIfNotNilAndPushMetrics($this->addressEntity, $difference, $stakeholderId, "");
        return $asvAddressEntity;
    }

    public function logDifferenceIfNotNilAndPushMetrics(string $entityName, array $difference, string $id, string $merchant_id) {
        if(count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                'entity_name' => $entityName,
                'difference' => $difference,
                'stakeholder_id' => $id ,
                'merchant_id' =>$merchant_id
            ]);
            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$entityName]);
        }
    }

}

