<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Merchant\Acs\AsvClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Modules\Acs\Comparator\MerchantComparator;
use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use Throwable;

class Merchant extends Base
{
    public $saveApiHelper;

    public $accountAsvClient;
    protected $merchantComparator;
    protected $entityName = "merchant";

    function __construct()
    {
        parent::__construct();
        $this->saveApiHelper = new SaveApiHelper();
        $this->accountAsvClient = new AsvClient\AccountAsvClient();
        $this->merchantComparator = new MerchantComparator();
    }

    /**
     * @param MerchantEntity $entity
     * @throws \Google\ApiCore\ValidationException
     * @throws \RZP\Exception\IntegrationException
     */
    function SaveOrFail(MerchantEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    /**
     * @throws Throwable
     */
    public function FindOrFail($merchantId, $apiMerchantEntity): MerchantEntity
    {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->findOrFailShadow($merchantId, $apiMerchantEntity);
            return $apiMerchantEntity;
        }
        else if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->findOrFailReverseShadow($merchantId, $apiMerchantEntity);
        }

        return $apiMerchantEntity;
    }

    public function findOrFailShadow(string $merchantId, $apiMerchantEntity)
    {
        try {
            $this->FetchByMerchantIdAndCompare($merchantId, $apiMerchantEntity);
        }catch (Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $merchantId,
                    "entity" => $this->entityName,
                ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function findOrFailReverseShadow(string $merchantId, $apiMerchantEntity): MerchantEntity
    {
        try {
            $asvMerchant = $this->FetchByMerchantIdAndCompare($merchantId, $apiMerchantEntity);
            return ASVEntityMapper::OverwriteWithAsvEntity(
                $apiMerchantEntity->toArray(),
                $asvMerchant->toArray(),
                MerchantEntity::class
            );
        } catch (Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::ASV_REVERSE_SHADOW_EXCEPTION,
                [
                    "id" => $merchantId,
                    "entity" => $this->entityName,
                ]);

            // rethrow the exception, fail the read on API
            throw $ex;
        }
    }

    function FetchByMerchantIdAndCompare(string $id, MerchantEntity $apiMerchantEntity): MerchantEntity
    {
        $fieldMask = new \Google\Protobuf\FieldMask([
            'paths' => ["merchant"]
            ]
        );
        $res = $this->accountAsvClient->FetchMerchant($id, $fieldMask);
        $merchant = $res->getMerchant();
        $asvMerchantEntity = ASVEntityMapper::MapProtoObjectToEntity($merchant, MerchantEntity::class);
        $difference = $this->merchantComparator->getDifference($apiMerchantEntity->toArray(), $asvMerchantEntity->toArray());
        if (count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                "entity" => $this->entityName,
                "id" => $id,
                "difference" => $difference
            ]);

            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$this->entityName]);
        }
        return $asvMerchantEntity;
    }
}
