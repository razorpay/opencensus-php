<?php

namespace RZP\Models\Merchant\Acs\ImplicitJoinHelper;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\Wrapper\Constant;

class ImplicitJoinHelper
{
    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];
    }

    public function getMerchantWebsiteAttributeByMerchantId($classInstance, $entityName, $relationName ='merchantWebsite')
    {
        $merchantWebsite = null;
        if ($classInstance->relationLoaded($relationName) === true)
        {
            $merchantWebsite = $classInstance->getRelation($relationName);
        }

        if ($merchantWebsite !== null)
        {
            return $merchantWebsite;
        }

        $merchantWebsite = app('repo')->merchant_website->getWebsiteDetailsForMerchantIdForImplicitJoin($classInstance->getMerchantId(), $entityName);

        $classInstance->setRelation($relationName, $merchantWebsite);

        return $merchantWebsite;
    }
}

