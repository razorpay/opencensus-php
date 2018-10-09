<?php

namespace RZP\Models\FundTransfer\Base\Beneficiary;

use RZP\Trace\TraceCode;
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

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_DATA_FETCHED);

        $file = $this->generateFile($data);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_FILE_CREATED);

        $merchantCount = count($data);

        $response = $this->makeResponse($file, $merchantCount);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_RESPONSE, ['response' => $response]);

        return $response;
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



