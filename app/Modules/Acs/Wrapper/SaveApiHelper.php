<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use Google\ApiCore\ValidationException;
use RZP\Exception\IntegrationException;

class SaveApiHelper extends Base
{
    public $saveApiAsvClient;

    function __construct()
    {
        parent::__construct();
        $this->saveApiAsvClient = new AsvClient\SaveApiAsvClient();
    }


    /**
     * This is generic saveHelper which can be used for every entity saveOrFail
     * @param string $merchantId
     * @param string $entityName
     * @param string $childEntityName
     * @param array $entity
     * @throws IntegrationException
     * @throws ValidationException
     */
    public function saveOrFail(string $merchantId, string $entityName, string $childEntityName, array $entity = [])
    {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, 'shadow', 'write') === true) {
            try {
                $this->saveApiAsvClient->SaveEntity($merchantId, $entityName, $entity);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $merchantId, 'entity_name' => $entityName,
                    'child_entity_name' => $childEntityName, 'operation' => 'write->save', 'mode' => 'shadow'
                ]);
            }
        } else if ($this->isShadowOrReverseShadowOnForOperation($merchantId, 'reverse_shadow', 'write') === true) {
            try {
                $this->saveApiAsvClient->SaveEntity($merchantId, $entityName, $entity);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $merchantId, 'entity_name' => $entityName,
                    'child_entity_name' => $childEntityName, 'operation' => 'write->save', 'mode' => 'reverse_shadow'
                ]);
                throw $e;
            }
        }
    }
}
