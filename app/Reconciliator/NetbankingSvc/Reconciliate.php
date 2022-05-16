<?php

namespace RZP\Reconciliator\NetbankingSvc;

use RZP\Exception;
use phpseclib\Crypt\AES;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\AESCrypto;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const GATEWAY_MERCHANT_ID       = 'MID';
    const PAYMENT_ID                = 'CRN';
    const BANK_REFERENCE_NUMBER     = 'TID';
    const PAYMENT_AMOUNT            = 'TransactionAmount';
    const PAYMENT_STATUS            = 'Status';
    const PAYMENT_DATE              = 'TRANSACTIONDATE (YYYYMMDD)';

    const PAYMENT_SUCCESS = 'Y';

    public static $columnHeaders = [
        self::GATEWAY_MERCHANT_ID,
        self::PAYMENT_ID,
        self::BANK_REFERENCE_NUMBER,
        self::PAYMENT_AMOUNT,
        self::PAYMENT_STATUS,
        self::PAYMENT_DATE,
    ];

    public function getColumnHeadersForType($type)
    {
        return self::$columnHeaders;
    }

    public function getDelimiter()
    {
        return '^';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDecryptedFile(array & $fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $encryptedData = file_get_contents($filePath);

        if ($encryptedData === false)
        {
            throw new Exception\ReconciliationException(
                'file content decryption failure', ['file_type' => $filePath]
            );
        }

        $config = $this->config['gateway.netbanking_svc'];

        $key = $config['encryption_key'];

        $iv = $config['encryption_iv'];

        $masterKey = hex2bin(md5($key)); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto

        $aes = new AESCrypto(AES::MODE_CBC, $masterKey, base64_decode($iv));

        $decryptedString = $aes->decryptString(hex2bin(trim($encryptedData)));

       file_put_contents($filePath, $decryptedString);
    }
}
