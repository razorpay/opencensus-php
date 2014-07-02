<?php
namespace Models\Manager;

use Utility;

class Token extends EntityManager
{
    protected static $createRules = array(
        'merchant_id' => 'required|numeric',
        'card'        => 'required');

    protected static $generators = array('token', 'expired');

    private static $TOKEN_LEN = \Constants\Fields::ID_LENGTH;

    protected function generateToken()
    {
        $token = Utility::generate_token(static::$TOKEN_LEN);

        $this->setField('id', $token);
    }

    protected function generateExpired()
    {
        $this->setField('expired', 0);
    }
}
