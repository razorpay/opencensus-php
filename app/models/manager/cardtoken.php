<?php 
namespace Models\Manager;

use Utility;

class CardToken extends EntityManager
{
    protected static $createRules = array(
        'merchant_id' => 'required|numeric',
        );

    protected static $generators = array('token', 'expired');

    private static $TOKEN_LEN = 16;

    protected function generateToken()
    {
        $token = Utility::generate_token(static::$TOKEN_LEN);
        
        $this->setField('token', $token);
    }

    protected function generateExpired()
    {
    	$this->setField('expired', 0);
    }

    public static function separateTokenAndCardCreateInput($input)
    {
    	return \break_assoc_array(
					$input,
					Card::getCreateInputKeys(),
					CardToken::getCreateInputKeys());
    }

}
