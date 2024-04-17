<?php

namespace RZP\Models\Merchant\Acs\ImplicitJoinHelper;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Trace\TraceCode;

class ImplicitJoinHelper
{
    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    const ASSOCIATED_RELATIONS = [
        "merchant",
        "parent",
        "parentMerchant"
    ];

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];
    }

    public function getRelationAttribute($classInstance, $entityName, $relationName, $repositoryInstance, $repositoryMethod, $fetchMethod)
    {
        $relationData = null;

        if ($classInstance->relationLoaded($relationName))
        {
            $relationData = $classInstance->getRelation($relationName);
        }

        if ($relationData !== null)
        {
            return $relationData;
        }

        $repo = app('repo');
        $relationData = $repo->$repositoryInstance->$repositoryMethod($classInstance->$fetchMethod(), $entityName);
        if(in_array($relationName, self::ASSOCIATED_RELATIONS, true) === true)
        {
            $classInstance->$relationName()->associate($relationData);
        }
        else
        {
            $classInstance->setRelation($relationName, $relationData);
        }

        $this->setParentMode($classInstance, $relationData);
        return $relationData;
    }

    public function setParentMode($parentInstance, $data): void
    {
        try
        {
            $parent_mode = $parentInstance->getConnectionName();
            if($parent_mode != null && $data instanceof \Illuminate\Database\Eloquent\Model)
            {
                $data->setConnection($parent_mode);
            }
        } catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR ,TraceCode::SET_PARENT_MODE_FAILURE);
        }
    }

    public function getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, $repositoryInstance, $repositoryMethod, $fetchMethod='getMerchantId')
    {
        return $this->getRelationAttribute($classInstance, $entityName, $relationName, $repositoryInstance, $repositoryMethod, $fetchMethod);
    }

    public function getRelationAttributeById($classInstance, $entityName, $relationName, $repositoryInstance, $repositoryMethod)
    {
        return $this->getRelationAttribute($classInstance, $entityName, $relationName, $repositoryInstance, $repositoryMethod, 'getId');
    }

    public function getMerchantWebsiteAttributeByMerchantId($classInstance, $entityName, $relationName = 'merchantWebsite', )
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_website', 'getWebsiteDetailsForMerchantIdForImplicitJoin');
    }


    public function getBusinessDetailAttributeByMerchantId($classInstance, $entityName, $relationName = 'businessDetail')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_business_detail', 'getBusinessDetailsForMerchantIdForImplicitJoin');
    }

    public function getMerchantDetailAttributeById($classInstance, $entityName, $relationName = 'merchantDetail')
    {
        return $this->getRelationAttributeById($classInstance, $entityName, $relationName, 'merchant_detail', 'findForImplicitJoin');
    }

    public function getStakeholderAttributeByMerchantId($classInstance, $entityName, $relationName = 'stakeholder')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'stakeholder', 'getStakeholderForMerchantIdForImplicitJoin');
    }

    public function getMerchantDocumentsAttributeByMerchantId($classInstance, $entityName, $relationName = 'merchantDocuments')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_document', 'getDocumentsForMerchantIdForImplicitJoin');
    }

    public function getMerchantAttributeById($classInstance, $entityName, $relationName = 'merchant')
    {
        return $this->getRelationAttributeById($classInstance, $entityName, $relationName, 'merchant', 'findForImplicitJoin');
    }

    public function getMerchantAttributeByMerchantId($classInstance, $entityName, $relationName = 'merchant', $fetchMethod = "getMerchantId")
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant', 'findForImplicitJoin', $fetchMethod);
    }

    public function getMerchantDetailAttributeByMerchantId($classInstance, $entityName, $relationName = 'merchantDetail')
    {
        return $this->getRelationAttributeByMerchantId($classInstance, $entityName, $relationName, 'merchant_detail', 'findForImplicitJoin');
    }

    public function getEmailsAttributeByMerchantId($classInstance, $entityName, $relationName = 'emails')
    {
        return $this->getRelationAttributeByMerchantId(
            $classInstance,
            $entityName,
            $relationName,
            'merchant_email',
            'getEmailByMerchantId',
        );
    }

}

