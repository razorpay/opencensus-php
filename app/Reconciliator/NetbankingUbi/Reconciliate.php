<?php

namespace RZP\Reconciliator\NetbankingUbi;

use phpseclib\Crypt\AES;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_DATE              = 'Trasanction Date (MM/DD/YY)';
    const BANK_REFERENCE_NUMBER     = 'PRN';
    const PAYMENT_ID                = 'RazorPay(Hardcoded Value)';
    const ACCOUNT_NUMBER            = 'Account Number';
    const AMOUNT                    = 'Amount';

    public static $columnHeaders = [
        self::BANK_REFERENCE_NUMBER,
        self::AMOUNT,
        self::PAYMENT_DATE,
        self::PAYMENT_ID,
        self::ACCOUNT_NUMBER,
    ];

    public function getColumnHeadersForType($type)
    {
        return self::$columnHeaders;
    }

    public function getDelimiter()
    {
        return '|';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDecryptedFile(array & $fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $config = $this->config['gateway.netbanking_ubi'];

        $encryptedText = file_get_contents($filePath);

        $aes = new AES(AES::MODE_ECB);

        $aes->setKey($config['recon_key']);

        $decryptedText = $aes->decrypt(base64_decode($encryptedText));

        if ($decryptedText !== false || $decryptedText !== "")
        {
            file_put_contents($filePath, $decryptedText);
        }
        else
        {
            file_put_contents($filePath, $encryptedText);
        }
    }
}
