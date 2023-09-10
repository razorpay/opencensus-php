<?php

namespace RZP\Models\Merchant\Acs\ParityChecker;

use App;
use RZP\Constants\Environment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Base\RepositoryManager;
use RZP\Modules\Acs\Comparator;
use  RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Service
{
    protected $app;

    /** @var Logger */
    protected $trace;

    /** @var RepositoryManager */
    protected $repo;

    protected $merchantIds;

    protected $parityCheckEntities;

    protected $parityCheckMethods;

    /**
     * @var Comparator\Base
     */
    protected $comparator;

    protected string $parityCheckType;

    protected $factory;

    function __construct(array $merchantIds, string $parityCheckEntity, array $parityCheckMethods, string $parityCheckType = 'read')
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $app[Constant::TRACE];
        $this->merchantIds = $merchantIds;
        #TODO: Add the list of all entities
        $this->parityCheckEntities = ($parityCheckEntity === Constant::ALL_ENTITY ? [Constant::MERCHANT_WEBSITE] : [$parityCheckEntity]);
        $this->parityCheckMethods = $parityCheckMethods;
        $this->parityCheckType = $parityCheckType;
        $this->factory = new Factory();
    }

    function triggerParityCheck(): array
    {
        if ($this->parityCheckType === Constant::READ) {
             $this->triggerReadParityCheck();
             return [];
        } else {
            return $this->triggerWriteParityCheck();
        }
    }


    function triggerWriteParityCheck(): array
    {

        $env = getenv("APP_ENV");
        if(Environment::isEnvironmentQA($env)===false and Environment::isLowerEnvironment($env) ===false){
            return [
                Constant::SUCCESS => false,
                Constant::ERROR => 'Parity check is not allowed in production environment.'
            ];
        }

        if (count($this->parityCheckEntities) != 1) {
            return [
                Constant::SUCCESS => false,
                Constant::ERROR => 'Parity check entity must be exactly one for writes.',
            ];
        }


        $entity = $this->parityCheckEntities[0];
        $this->trace->info(TraceCode::ASV_TRIGGER_PARITY_CHECK_FOR_ENTITY, ['entity' => $entity, 'flow' => 'write']);
            try {
                $parityCheckerEntityClass = $this->factory->getEntityParityCheckerClass($entity);
                /**
                 * @var $parityCheckerEntityObject ParityInterface
                 * merchant id is not required for write flow.
                 */
                $parityCheckerEntityObject = new $parityCheckerEntityClass("", $this->parityCheckMethods);

                return $parityCheckerEntityObject->checkWriteParity();
            } catch (\Exception $e) {
                $this->trace->traceException($e, null, TraceCode::ASV_TRIGGER_PARITY_CHECK_FOR_ENTITY_ERROR);
                return [
                        Constant::SUCCESS => false,
                        Constant::ERROR => $e->getMessage(),
                    ];
            }

    }

    function triggerReadParityCheck()
    {
        foreach ($this->merchantIds as $merchantId) {

            foreach ($this->parityCheckEntities as $entity) {
                $this->trace->info(TraceCode::ASV_TRIGGER_PARITY_CHECK_FOR_ENTITY, ['entity' => $entity, 'merchant_id' => $merchantId]);
                try {
                    $parityCheckerEntityClass = $this->factory->getEntityParityCheckerClass($entity);
                    /**
                     * @var $parityCheckerEntityObject ParityInterface
                     */
                    $parityCheckerEntityObject = new $parityCheckerEntityClass($merchantId, $this->parityCheckMethods);

                    return $parityCheckerEntityObject->checkReadParity();
                } catch (\Exception $e) {
                    $this->trace->traceException($e, null, TraceCode::ASV_TRIGGER_PARITY_CHECK_FOR_ENTITY_ERROR);
                }
            }
        }

        return [];
    }
}
