<?php

namespace RZP\Reconciliator\RequestProcessor;

use RZP\Reconciliator\RequestProcessor\Retriever\DataRetrieverManager;
use Symfony\Component\HttpFoundation\File\File;

use Config;
use RZP\Exception;
use RZP\Models\FileStore\Utility;
use RZP\Reconciliator\FileProcessor;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Crawler extends Base
{
    use FileHandlerTrait;

    public function process(array $input): array
    {

        $this->setGatewayFromInput($input);

        $this->setGatewayReconciliatorObject();

        $files = DataRetrieverManager::getDataRetriever($this->gateway)->fetchData($input);

        $fileCount = 0;
        $input = [];
        foreach ($files as $file){
            $input[self::ATTACHMENT_HYPHEN_PREFIX . ++$fileCount] = $file;
        }

        $inputDetails = [
            self::ATTACHMENT_COUNT => $fileCount,
            self::GATEWAY          => $this->gateway,
            self::SOURCE           => self::CRAWLER,
        ];

        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input, FileProcessor::STORAGE);

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $inputDetails,
        ];
    }

    /**
     * Fetch Gateway name from input and check if it is in allowed list.
     *
     * @param $input
     * @param string $key
     *
     * @throws Exception\ReconciliationException
     */
    protected function setGatewayFromInput($input)
    {
        if (isset($input[self::GATEWAY]) === true)
        {
            $this->gateway = $input[self::GATEWAY];
        }

        if (in_array($this->gateway, self::GATEWAY_CRAWLERS) === false)
        {
            throw new Exception\ReconciliationException(
                'Invalid gateway param. Not in the allowed list of gateway params.',
                [
                    'gateway' => $this->gateway
                ]);
        }
    }
}
