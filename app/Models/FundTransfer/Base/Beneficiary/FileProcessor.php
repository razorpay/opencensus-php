<?php

namespace RZP\Models\FundTransfer\Base\Beneficiary;

use RZP\Exception;
use RZP\Models\FileStore\Storage\Base\Bucket;
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
    public function registerBeneficiary(PublicCollection $bankAccounts): array
    {
        $file          = new FileStore\Creator;

        $fileCreated   = false;

        $totalCount    = $bankAccounts->count();

        $data          = $this->getData($bankAccounts);

        $registerCount = count($data);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_DATA_FETCHED);

        if ($registerCount !== 0)
        {
            $file        = $this->generateFile($data);

            $fileCreated = true;

            $this->trace->info(TraceCode::BENEFICIARY_REGISTER_FILE_CREATED);

        }

        $response = $this->makeResponse($file, $totalCount, $registerCount, $fileCreated);

        $this->trace->info(TraceCode::BENEFICIARY_REGISTER_RESPONSE, ['response' => $response]);

        return $response;
    }

    /**
     * Place holder method for verifyBeneficiary since parent abstract class has it.
     * @param PublicCollection $bankAccounts
     * @return array
     * @throws Exception\LogicException
     */
    public function verifyBeneficiary(PublicCollection $bankAccounts): array
    {
        throw new Exception\LogicException('Beneficiary verification not supported for channel '.$this->channel);

        return [];
    }

    /**
     * Creates summary response for the bene addition process
     *
     * @param FileStore\Creator $file
     * @param int $totalCount
     * @param int $registerCount
     * @param bool $fileCreated
     * @return array
     */
    protected function makeResponse(
        FileStore\Creator $file,
        int $totalCount,
        int $registerCount,
        bool $fileCreated
    ): array
    {
        $fileInfo = [];

        if ($fileCreated !== false)
        {
            $fileDetails   = $file->get();

            $fileInfo = [
                'signed_url'      => $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'],
                'file_name'       => basename($fileDetails['local_file_path']),
                'local_file_path' => $fileDetails['local_file_path'],
            ];
        }

        $data = [
            'channel'        => $this->channel,
            'total_count'    => $totalCount,
            'register_count' => $registerCount,
        ] + $fileInfo;

        return $data;
    }

    public function getBucketConfig(string $type, string $env)
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($type, $env);

        return $config[$bucketType];
    }
}
