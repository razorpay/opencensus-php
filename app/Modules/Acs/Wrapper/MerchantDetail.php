<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Constants\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Comparator\MerchantDetailComparator;
use RZP\Trace\TraceCode;
use Throwable;

class MerchantDetail extends Base
{
    public $accountAsvClient;
    protected $entityName = "merchant_detail";
    /**
     * @var MerchantDetailComparator
     */
    private $merchantDetailComparator;
    public $saveApiHelper;

    function __construct()
    {
        parent::__construct();
        $this->accountAsvClient = new AsvClient\AccountAsvClient();
        $this->merchantDetailComparator = new MerchantDetailComparator();
        $this->saveApiHelper = new SaveApiHelper();
    }
    /**
     * @throws Throwable
     */
    public function getByMerchantId($merchantId, $apiMerchantDetailEntity): MerchantDetailEntity
    {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadow($merchantId, $apiMerchantDetailEntity);
            return $apiMerchantDetailEntity;
        }
        else if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReverseShadowAndOverwriteWithAsvResponse($merchantId, $apiMerchantDetailEntity);
        }

        return $apiMerchantDetailEntity;
    }


    /**
     * @param MerchantDetailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantDetailEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    public function processReadShadow(string $merchantId, $apiMerchantDetailEntity)
    {
        try {
            $this->FetchByMerchantIdAndCompare($merchantId, $apiMerchantDetailEntity);
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
    public function processReverseShadowAndOverwriteWithAsvResponse(string $merchantId, $apiMerchantDetailEntity): MerchantDetailEntity
    {
        try {
            $asvMerchantDetail = $this->FetchByMerchantIdAndCompare($merchantId, $apiMerchantDetailEntity);
            return ASVEntityMapper::OverwriteWithAsvEntity(
                $apiMerchantDetailEntity->toArray(),
                $asvMerchantDetail->toArray(),
                MerchantDetailEntity::class
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

    function FetchByMerchantIdAndCompare(string $id, MerchantDetailEntity $apiMerchantDetailEntity): MerchantDetailEntity
    {
        $fieldMask = new \Google\Protobuf\FieldMask([ 'paths' => ["merchant_detail"]
            ]
        );
        $res = $this->accountAsvClient->FetchMerchant($id, $fieldMask);
        $merchantDetail = $res->getMerchantDetail();
        $asvMerchantDetailEntity = ASVEntityMapper::MapProtoObjectToEntity($merchantDetail, MerchantDetailEntity::class);
        $difference = $this->merchantDetailComparator->getDifference($apiMerchantDetailEntity->toArray(), $asvMerchantDetailEntity->toArray());
        if (count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                "entity" => $this->entityName,
                "id" => $id,
                "difference" => $difference
            ]);

            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$this->entityName]);
        }
        return $asvMerchantDetailEntity;
    }
}
