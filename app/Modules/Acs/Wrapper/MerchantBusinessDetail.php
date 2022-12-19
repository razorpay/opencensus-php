<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Merchant\Acs\AsvClient\AccountAsvClient;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Comparator\MerchantBusinessDetailComparator;
use RZP\Models\Merchant\BusinessDetail\Entity as MerchantBusinessDetailEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use Throwable;


class MerchantBusinessDetail extends Base
{
    public $accountAsvClient;
    protected $entityName = "merchant_business_detail";
    /**
     * @var MerchantBusinessDetailComparator
     */
    private $merchantBusinessDetailComparator;
    public $saveApiHelper;

    function __construct()
    {
        parent::__construct();
        $this->accountAsvClient = new AccountAsvClient();
        $this->merchantBusinessDetailComparator = new MerchantBusinessDetailComparator();
        $this->saveApiHelper = new SaveApiHelper();
    }

    public function GetMerchantBusinessDetailForMerchantId(string $merchantId, $businessDetailFromAPI) {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadow($merchantId, $businessDetailFromAPI);
            return $businessDetailFromAPI;
        }
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReverseShadowAndOverwriteWithAsvResponse($merchantId, $businessDetailFromAPI);
        }

        return $businessDetailFromAPI;
    }

    /**
     * @param MerchantBusinessDetailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantBusinessDetailEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    private function processReadShadow(string $merchantId, $businessDetailsFromAPI)
    {
        try {
            $this->FetchByMerchantIdAndCompare($merchantId, $businessDetailsFromAPI);
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
    private function processReverseShadowAndOverwriteWithAsvResponse(string $merchantId, $businessDetailsFromAPI)
    {
        try {
            $asvMerchantBusinessDetails= $this->FetchByMerchantIdAndCompare($merchantId, $businessDetailsFromAPI);
            return ASVEntityMapper::OverwriteWithAsvEntity(
                $businessDetailsFromAPI->toArray(),
                $asvMerchantBusinessDetails->toArray(),
                MerchantBusinessDetailEntity::class
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

    private function FetchByMerchantIdAndCompare(string $merchantId, $businessDetailFromAPI)
    {
        $fieldMask = new \Google\Protobuf\FieldMask([ 'paths' => ["merchant_business_detail"]
            ]
        );
        $res = $this->accountAsvClient->FetchMerchant($merchantId, $fieldMask);
        $merchantBusinessDetail = $res->getMerchantBusinessDetail();
        $asvMerchantBusinessDetailEntity = ASVEntityMapper::MapProtoObjectToEntity($merchantBusinessDetail, MerchantBusinessDetailEntity::class);
        $difference = $this->merchantBusinessDetailComparator->getDifference($businessDetailFromAPI->toArray(), $asvMerchantBusinessDetailEntity->toArray());
        if (count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                "entity" => $this->entityName,
                "id" => $merchantId,
                "difference" => $difference
            ]);

            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$this->entityName]);
        }
        return $asvMerchantBusinessDetailEntity;
    }
}
