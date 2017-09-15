<?php

namespace RZP\Models\FileStore;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Encryption
{
    const PGP_ENCRYPTION = 'pgp_encryption';

    const VALID_ENCRYPTION_TYPES = [
        self::PGP_ENCRYPTION,
    ];

     /**
     * Encrypts the file
     *
     * @param string $type     encryption type
     * @param string $secret   encryption secret
     * @param string $filePath Full File Path
     *
     * @return null
     * @throws Exception\LogicException
     */
    public function encrypt(string $type, string $secret, string $filePath)
    {
        $this->validateEncryptionType($type);

        $function = 'do' . studly_case($type);

        $this->$function($secret, $filePath);
    }

    protected function validateEncryptionType(string $type)
    {
        if (in_array($type, self::VALID_ENCRYPTION_TYPES) === false)
        {
            //throw exception
        }
    }

    protected function doPgpEncryption($secret, $filePath)
    {
        $res = gnupg_init();

        gnupg_addencryptkey($res,$secret);

        $data = file_get_contents($filePath);

        $enc = gnupg_encrypt($res, $data);

        file_put_contents($filePath, $enc);
    }
}
