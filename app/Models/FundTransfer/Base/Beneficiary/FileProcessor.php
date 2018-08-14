<?php

namespace RZP\Models\FundTransfer\Base\Beneficiary;

use RZP\Models\FileStore;
use RZP\Models\Base\PublicCollection;

abstract class FileProcessor extends Beneficiary
{
    const SIGNED_URL_DURATION = '1440';

    /**
     * Gets the bene data required to be written to file
     *
     * @param PublicCollection $bankAccounts
     * @return array
     */
    abstract protected function getData(PublicCollection $bankAccounts): array;

    /**
     * Generates the settlement file based on given data
     *
     * @param $data
     * @return FileStore\Creator
     */
    abstract protected function generateFile($data): FileStore\Creator;

    /**
     * collects the bene data, creates file and gives back the summary
     *
     * @param PublicCollection $bankAccounts
     * @return array
     */
    protected function registerBeneficiary(PublicCollection $bankAccounts): array
    {
        $data = $this->getData($bankAccounts);

        $file = $this->generateFile($data);

        $merchantCount = count($data);

        return $this->makeResponse($file, $merchantCount);
    }

    /**
     * Creates summary response for the bene addition process
     *
     * @param FileStore\Creator $file
     * @param int               $merchantCount
     * @return array
     */
    protected function makeResponse(FileStore\Creator $file, int $merchantCount): array
    {
        $fileDetails   = $file->get();

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $data = [
            'signed_url'      => $signedFileUrl,
            'local_file_path' => $fileDetails['local_file_path'],
            'file_name'       => basename($fileDetails['local_file_path']),
            'merchants_count' => $merchantCount,
            'channel'         => $this->channel,
        ];

        return $data;
    }
}



