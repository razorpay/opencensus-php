<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Constants\Metric;
use RZP\Exception\IntegrationException;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Modules\Acs\Comparator\MerchantEmailComparator;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class MerchantEmail extends Base
{
    public $accountAsvClient;
    /**
     * @var MerchantEmailComparator
     */
    private $merchantEmailComparator;

    public $saveApiHelper;
    private string $entityName;

    function __construct()
    {
        parent::__construct();
        $this->accountAsvClient = new AsvClient\AccountAsvClient();
        $this->merchantEmailComparator = new MerchantEmailComparator();
        $this->saveApiHelper = new SaveApiHelper();
        $this->entityName = 'merchant_email';
    }

    /**
     * @param MerchantEmailEntity $entity
     * @throws IntegrationException
     */
    public function Delete(MerchantEmailEntity $entity)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'shadow', 'write') === true) {
            try {
                $this->accountAsvClient->DeleteAccountContact($entity['id'], $entity['merchant_id'], $entity['type']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'shadow'
                ]);
            }
        } else if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'reverse_shadow', 'write') === true) {
            try {
                $this->accountAsvClient->DeleteAccountContact($entity['id'], $entity['merchant_id'], $entity['type']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'reverse_shadow'
                ]);
                throw $e;
            }
        }
    }

    /**
     * @param MerchantEmailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantEmailEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    /**
     * @param string $merchantID
     * @return PublicCollection
     * @throws \RZP\Exception\IntegrationException
     */
    function FetchMerchantEmailsFromMerchantId(string $merchantID, $emailsFromAPI)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantID, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->processReadShadowFetchMerchantEmailsByMerchantId($merchantID, $emailsFromAPI);
            return $emailsFromAPI;
        } else if ($this->isShadowOrReverseShadowOnForOperation($merchantID, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->processReadReverseShadowFetchMerchantEmailsByMerchantId($merchantID, $emailsFromAPI);
        }
        return $emailsFromAPI;
    }

    function FetchAndCompareMerchantEmailsFromMerchantId(string $merchantID, $emailsFromAPI): PublicCollection
    {
        $fieldMask = new \Google\Protobuf\FieldMask([
                'paths' => ["merchant_email"]
            ]
        );
        $res = $this->accountAsvClient->FetchMerchant($merchantID, $fieldMask);
        $emailsFromASV = ASVEntityMapper::MapProtoObjectIteratorToEntityCollection($res->getMerchantEmails(), MerchantEmailEntity::class);
        $difference = $this->merchantEmailComparator->getDifferenceCompareByUniqueId($emailsFromAPI->toArray(), $emailsFromASV->toArray(), 'id');
        $this->logDifferenceIfNotNilAndPushMetrics($this->entityName, $difference, "", $merchantID);
        return new PublicCollection($emailsFromASV);
    }

    public function logDifferenceIfNotNilAndPushMetrics(string $entityName, array $difference, string $id, string $merchant_id) {
        if(count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                'entity_name' => $entityName,
                'difference' => $difference,
                'email_id' => $id ,
                'merchant_id' =>$merchant_id
            ]);
            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$entityName]);
        }
    }

    private function processReadShadowFetchMerchantEmailsByMerchantId(string $merchantID, $emailsFromAPI)
    {
        try {
            $this->FetchAndCompareMerchantEmailsFromMerchantId($merchantID, $emailsFromAPI);
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "merchant_id" => $merchantID,
                    "entity" => $this->entityName
                ]);
        }
    }

    /**
     * @param string $merchantID
     * @param $emailsFromAPI
     * @throws IntegrationException
     */
    private function processReadReverseShadowFetchMerchantEmailsByMerchantId(string $merchantID, $emailsFromAPI)
    {
        try {
            $emailsFromASV = $this->FetchAndCompareMerchantEmailsFromMerchantId($merchantID, $emailsFromAPI);
            return  $emailsFromASV;
        } catch (\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::ASV_REVERSE_SHADOW_EXCEPTION,
                [
                    "merchant_id" => $merchantID,
                    "entity" => $this->entityName
                ]);
            throw $ex;
        }
    }
}
