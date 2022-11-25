<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;

class MerchantDocument extends Base
{
    public $accountDocumentAsvClient;

    function __construct()
    {
        parent::__construct();
        $this->accountDocumentAsvClient = new AsvClient\AccountDocumentAsvClient();
    }

    /**
     * @param MerchantDocumentEntity $entity
     * @throws \RZP\Exception\IntegrationException
     */
    public function DeleteOrFail(MerchantDocumentEntity $entity)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'shadow', 'write') === true) {
            try {
                $this->accountDocumentAsvClient->DeleteAccountDocument($entity['id']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'shadow'
                ]);
            }
        } else if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'reverse_shadow', 'write') === true) {
            try {
                $this->accountDocumentAsvClient->DeleteAccountDocument($entity['id']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'reverse_shadow'
                ]);
                throw $e;
            }
        }
    }
}
