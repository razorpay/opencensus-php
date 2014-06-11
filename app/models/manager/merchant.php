<?php 

namespace Models\Manager;

use Utility;

class Merchant extends EntityManager
{
    protected static $createRules = array(
        'merchant_id'    =>  'required|numeric'
    );

    protected static $generators = array('id');

    protected static $unsetCreateInput = array('merchant_id');

    protected function generateId($input)
    {
        $this->setField('id', $input['merchant_id']);
    }

    public static function separateMerchantAndKeyCreateInput($input)
    {
        return \break_assoc_array(
                    $input,
                    Merchant::getCreateInputKeys(),
                    Key::getCreateInputKeys());
    }
}
