<?php

namespace RZP\Gateway\Netbanking\Canara;

use RZP\Gateway\Base;
use RZP\Constants\Mode;

class AESCrypto extends Base\AESCrypto
{
    public function __construct($mode, $config)
    {
        if ($mode == Mode::TEST or $config === null)
        {
            $masterKey = $config['test_master_key'];

            $iv = $config['test_IV'];
        }
        else
        {
            //TODO : Find out if this should be accessed directly from terminal check getSecret()
            $masterKey = $config['live_master_key'];

            $iv = $config['live_IV'];
        }

        parent::__construct(Constants::MODE_CBC, $masterKey, $iv);
    }

    public function encryptString(string $string)
    {
        return strtoupper(bin2hex(parent::encryptString($string)));
    }

    public function decryptString(string $string)
    {
        return $this->aes->decrypt(hex2bin($string));
    }
}
