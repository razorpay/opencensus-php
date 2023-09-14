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

    public function getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, $repositoryInstance, $repositoryMethod)
    {
            $relationData = null;

            if ($classInstance->relationLoaded($relationName)) {
                $relationData = $classInstance->getRelation($relationName);
            }

            if ($relationData !== null) {
                return $relationData;
            }

            $repo = app('repo');

            $relationData = $repo->$repositoryInstance->$repositoryMethod($classInstance->getMerchantId(), $entityName);
            $classInstance->setRelation($relationName, $relationData);

            return $relationData;
    }

    public function getMerchantWebsiteAttributeByMerchantId($classInstance, $entityName, $relationName = 'merchantWebsite', )
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_website', 'getWebsiteDetailsForMerchantIdForImplicitJoin');
    }


    public function getBusinessDetailAttributeByMerchantId($classInstance, $entityName, $relationName = 'businessDetail')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_business_detail', 'getBusinessDetailsForMerchantIdForImplicitJoin');
    }

    public function getStakeholderAttributeByMerchantId($classInstance, $entityName, $relationName = 'stakeholder')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'stakeholder', 'getStakeholderForMerchantIdForImplicitJoin');
    }

    public function getMerchantDocumentsAttributeByMerchantId($classInstance, $entityName, $relationName = 'merchantDocuments')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_document', 'getDocumentsForMerchantIdForImplicitJoin');
    }

}

