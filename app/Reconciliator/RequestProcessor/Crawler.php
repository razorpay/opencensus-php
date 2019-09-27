<?php

namespace RZP\Reconciliator\RequestProcessor;

use RZP\Reconciliator\RequestProcessor\Retriever\DataRetrieverManager;
use RZP\Trace\TraceCode;

use Config;
use RZP\Exception;
use RZP\Reconciliator\FileProcessor;

class Crawler extends Base
{

    public function process(array $input): array
    {
        $this->setGatewayFromInput($input);

        $this->setGatewayReconciliatorObject();

        $input['gateway'] = self::GATEWAY_CRAWLERS[$this->gateway];

        $files = DataRetrieverManager::getDataRetriever($this->gateway)->fetchData($input);

        $input['gateway'] = $this->gateway;

        $fileCount = 0;
        $input = [];
        foreach ($files as $file)
        {
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

        if (isset(self::GATEWAY_CRAWLERS[$this->gateway]) === false)
        {
            throw new Exception\ReconciliationException(
                'Invalid gateway param. Not in the allowed list of crawler gateway params.',
                [
                    'gateway' => $this->gateway
                ]);
        }
    }
}
