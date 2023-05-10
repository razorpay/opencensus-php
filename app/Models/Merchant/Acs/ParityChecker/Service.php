<?php

namespace RZP\Models\Merchant\Acs\ParityChecker;

use App;
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

    protected $factory;

    function __construct(array $merchantIds, string $parityCheckEntity, array $parityCheckMethods)
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $app[Constant::TRACE];
        $this->merchantIds = $merchantIds;
        #TODO: Add the list of all entities
        $this->parityCheckEntities = ($parityCheckEntity === Constant::ALL_ENTITY ? [Constant::MERCHANT_WEBSITE] : [$parityCheckEntity]);
        $this->parityCheckMethods = $parityCheckMethods;
        $this->factory = new Factory();
    }

    function triggerParityCheck(): void
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

                    $parityCheckerEntityObject->checkParity();
                } catch (\Exception $e) {
                    $this->trace->traceException($e, null, TraceCode::ASV_TRIGGER_PARITY_CHECK_FOR_ENTITY_ERROR);
                }
            }
        }
    }
}
