<?php

namespace Models\Settlement\Details;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ID                      =>      'required|max:14',
        Entity::MERCHANT_ID             =>      'required|max:14',
        Entity::SETTLEMENT_ID           =>      'required|max:14'            
        Entity::TYPE                    =>      'required|in:payment,refund,adjustment,fee,service_tax'    
        Entity::COUNT                   =>      'required|integer'    
        Entity::AMOUNT                  =>      'required|integer'    
        Entity::DESCRIPTION             =>      'sometimes'        
    );
}