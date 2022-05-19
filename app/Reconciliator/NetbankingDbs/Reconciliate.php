<?php

namespace RZP\Reconciliator\NetbankingDbs;

use RZP\Reconciliator\Base;
use RZP\Encryption\PGPEncryption;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_ID            = 'MERCHANT_ORDER_ID';
    const REFUND_ID             = 'MERCHANT_ORDER_ID';
    const TRANSACTION_AMOUNT    = 'TRANSACTION_AMOUNT';
    const BANK_REF_NO           = 'TRANSACTION_REFERENCE_NUMBER';
    const TRANSACTION_TYPE      = 'ORDER_TYPE';
    const TRANSACTION_STATUS    = 'TRANSACTION_STATUS';
    const TRANSACTION_DATE      = 'TRANSACTION_REQUESTED_DATE';

    const PAYMENT_COLUMN_HEADERS = [
        self::PAYMENT_ID,
        self::TRANSACTION_AMOUNT,
        self::BANK_REF_NO,
        self::TRANSACTION_TYPE,
        self::TRANSACTION_STATUS,
        self::TRANSACTION_DATE
    ];

    const TRANSACTION_SUCCESS   = 'SUCCESS';

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }

    public function getColumnHeadersForType($type)
    {
        return self::PAYMENT_COLUMN_HEADERS;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP       => 1,
            FileProcessor::LINES_FROM_BOTTOM    => 0
        ];
    }

    public function getDecryptedFile(array & $fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $config = $this->config['gateway.netbanking_dbs'];

        $pgpConfig = [
            PGPEncryption::PRIVATE_KEY  => trim(str_replace('\n', "\n", $config['recon_key']))
        ];

        $encryptedText = file_get_contents($filePath);

        $res = new PGPEncryption($pgpConfig);

        $decryptedText = $res->decrypt($encryptedText);

        file_put_contents($filePath, $decryptedText);
    }
}
