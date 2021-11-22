<?php

namespace RZP\Services\AutoGenerateApiDocs;

use RZP\Services\AutoGenerateApiDocs\SaveApiDetails;

class CombineApiDetailsFile
{
    protected $fileNames = [];

    protected $allApisDetails = [];

    protected $fileDir;

    protected $totalApiDetailsFiles;

    protected $combinedApiDetailsFilesPath;

    public function __construct(int $totalApiDetailsFiles, string $fileDir, string $combinedApiDetailsFilesPath)
    {
        $this->totalApiDetailsFiles         = $totalApiDetailsFiles;

        $this->fileDir                      = $fileDir;

        $this->combinedApiDetailsFilesPath  = $combinedApiDetailsFilesPath;

        $this->fileNames                    = array_diff(scandir($this->fileDir), array('.', '..'));
    }

    public function combine()
    {
        if($this->isAllFilesGenerated() === false)
        {
            return;
        }

        foreach ($this->fileNames as $fileName)
        {
            $filePath = $this->fileDir.$fileName;

            if(file_exists($filePath) === false)
            {
                print("====Error: File ." . $fileName . " Not Found ==========".PHP_EOL);
                continue;
            }

            $apisDetails = json_decode(file_get_contents($filePath), true);

            foreach ($apisDetails as $apiIdentifier => $apis)
            {
                if(empty($this->allApisDetails[$apiIdentifier]) === false)
                {
                    foreach ($apis as $apiDataUniqueIdentifier => $apiDetails)
                    {
                        $this->allApisDetails[$apiIdentifier][$apiDataUniqueIdentifier] = $apiDetails;
                    }
                }
                else
                {
                    $this->allApisDetails[$apiIdentifier] = $apis;
                }
            }
        }

        file_put_contents($this->combinedApiDetailsFilesPath, json_encode($this->allApisDetails));
    }

    protected function getCombineFileData()
    {
        return file_get_contents($this->combinedApiDetailsFilesPath, json_decode($this->allApisDetails, true));
    }

    protected function isAllFilesGenerated()
    {
        return count($this->fileNames) === $this->totalApiDetailsFiles;
    }
}
