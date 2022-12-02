<?php

namespace RZP\Modules\Acs\Wrapper;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Comparator\MerchantWebsiteComparator;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use Throwable;

class MerchantWebsite extends Base
{
    public $saveApiHelper;
    private $merchantWebsiteClient;
    protected $merchantWebsiteComparator;
    protected $entityName = "merchant_website";

    function __construct()
    {
        parent::__construct();
        $this->merchantWebsiteClient = new AsvClient\WebsiteAsvClient;
        $this->merchantWebsiteComparator = new MerchantWebsiteComparator();
        $this->saveApiHelper = new SaveApiHelper();
    }

    public function setMerchantWebsiteClient($merchantWebsiteClient){
        $this->merchantWebsiteClient = $merchantWebsiteClient;
    }

    /**
     * @param MerchantWebsiteEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantWebsiteEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    /**
     * @throws Throwable
     */
    public function processGetWebsiteDetailsForMerchantId($merchantId, $apiMerchantWebsiteEntity): MerchantWebsiteEntity
    {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadowGetWebsiteDetailsForMerchantId($merchantId, $apiMerchantWebsiteEntity);
            return $apiMerchantWebsiteEntity;
        } else if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReverseShadowGetWebsiteDetailsForMerchantId($merchantId, $apiMerchantWebsiteEntity);
        }

        return $apiMerchantWebsiteEntity;
    }

    public function processReadShadowGetWebsiteDetailsForMerchantId(string $merchantId, $apiMerchantWebsiteEntity)
    {
        try {
            $this->FetchByMerchantIdAndCompare($merchantId, $apiMerchantWebsiteEntity);
        } catch (Throwable $ex) {
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
    public function processReverseShadowGetWebsiteDetailsForMerchantId(string $merchantId, $apiMerchantWebsiteEntity): MerchantWebsiteEntity
    {
        try {
            $asvWebsite = $this->FetchByMerchantIdAndCompare($merchantId, $apiMerchantWebsiteEntity);
            return ASVEntityMapper::OverwriteWithAsvEntity(
                $apiMerchantWebsiteEntity->toArray(),
                $asvWebsite->toArray(),
                MerchantWebsiteEntity::class
            );
        } catch (Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_REVERSE_SHADOW_EXCEPTION,
                [
                    "id" => $merchantId,
                    "entity" => $this->entityName,
                ]);

            // rethrow the exception, fail the read on API
            throw $ex;
        }
    }

    /**
     * @throws \RZP\Exception\IntegrationException
     */
    public function FetchByMerchantIdAndCompare(string $merchantId, MerchantWebsiteEntity $apiMerchantWebsite): MerchantWebsiteEntity
    {
        $asvMerchantWebsite = $this->merchantWebsiteClient->FetchMerchantWebsite($merchantId);

        $asvMerchantWebsiteEntity = ASVEntityMapper::MapProtoObjectToEntity($asvMerchantWebsite, MerchantWebsiteEntity::class);

        $difference = $this->merchantWebsiteComparator->getDifference($apiMerchantWebsite->toArray(), $asvMerchantWebsiteEntity->toArray());

        $this->logDifferenceIfRequiredAndPushMetrics($difference, $merchantId);

        return $asvMerchantWebsiteEntity;
    }


    public function logDifferenceIfRequiredAndPushMetrics(array $difference, string $merchantId) {
        if (count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                "entity" => $this->entityName,
                "merchant_id" => $merchantId,
                "difference" => $difference
            ]);
            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$this->entityName]);
        }
    }

}
