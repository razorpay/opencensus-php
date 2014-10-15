<?php

namespace Services;

use Queue;

class Dashboard
{
    public function queueRecord($resource, $data)
    {   
        if(is_a($data, 'Models\\Base\\PublicCollection') === true)
        {
            foreach($data as $entity)
            {
                //Recursively call this function for each entity in collection
                $this->queueRecord($resource, $entity);
            }
        }
        elseif(is_a($data, 'Models\\Base\\PublicEntity') === true)
        {
            $data = array_merge(
                    $data->toArray(),
                    ['merchant_id' => $data->getMerchantId()]);

            $mode = \BasicAuth::getMode();

            Queue::push('Dashboard\\'.ucwords($resource).'@postRequest', array(
                'mode'  => $mode,
                'message'   =>  $data
            ));
        }
    }
}
