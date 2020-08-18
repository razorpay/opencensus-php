<?php

namespace RZP\Reconciliator\NetbankingPnb;

use RZP\Reconciliator\Base;
use RZP\Encryption\PGPEncryption;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    public function getDecryptedFile(array & $fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $config = $this->config['gateway.netbanking_pnb'];

        $pgpConfig = [
            PGPEncryption::PRIVATE_KEY  => trim(str_replace('\n', "\n", $config['recon_key'])),
            PGPEncryption::PASSPHRASE   => $config['recon_passphrase']
        ];

        $encryptedText = file_get_contents($filePath);

        $res = new PGPEncryption($pgpConfig);

        $decryptedText = $res->decrypt($encryptedText);

        file_put_contents($filePath, $decryptedText);
    }
}
