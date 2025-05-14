<?php

namespace RZP\Models\Customer;

use RZP\Trace\TraceCode;

class ImplicitJoinHelper
{
    public function getCustomerAttributeByCustomerId($classInstance, $relationName = 'customer', $fetchMethod = null)
    {
        if ($classInstance->relationLoaded($relationName))
        {
            return $classInstance->getRelation($relationName);
        }

        if (is_null($fetchMethod))
            $customerId = $classInstance->getAttribute('customer_id');
        else
            $customerId = $classInstance->$fetchMethod();

        $relationData = app('repo')->customer->find($customerId);
        $classInstance->$relationName()->associate($relationData);
        return $relationData;
    }

//    public function setParentMode($parentInstance, $data): void
//    {
//        try
//        {
//            $parent_mode = $parentInstance->getConnectionName();
//            if($parent_mode != null && $data instanceof \Illuminate\Database\Eloquent\Model)
//            {
//                $data->setConnection($parent_mode);
//            }
//        } catch (\Exception $e)
//        {
//            $this->trace->traceException($e, Trace::ERROR ,TraceCode::SET_PARENT_MODE_FAILURE);
//        }
//    }
}
