<?php

namespace RZP\Services\AutoGenerateApiDocs;

use RZP\Services\AutoGenerateApiDocs\ApiDetails;

class SaveApiDetails
{
    protected $apiDetails;

    protected $fileName;

    protected $fileDir;

    protected $filePath = null;

    private $allApiDetails = [];

    public function __construct(ApiDetails $apiDetails, string $fileDir, string $fileName)
    {
        $this->fileDir           = $fileDir;

        $this->fileName          = $fileName;

        $this->apiDetails        = $apiDetails;

        $this->filePath          = $fileDir . $this->fileName;
    }

    public function save()
    {
        $this->initialiseFile();

        $this->getAllApisInfo();

        $apiIdentifier = $this->apiDetails->getRequestUrlWithVariable().$this->apiDetails->getRequestMethod();

        $apiDataUniqueIdentifier = $this->apiDetails->getApiDataUniqueIdentifier();

        if (empty($this->allApiDetails[$apiIdentifier][$apiDataUniqueIdentifier]) === false)
        {
            //===Already Saved this Api=======
            return;
        }

        $this->allApiDetails[$apiIdentifier][$apiDataUniqueIdentifier] = serialize($this->apiDetails);

        file_put_contents($this->filePath, json_encode($this->allApiDetails));
    }

    protected function initialiseFile()
    {
        if (file_exists($this->filePath) === false)
        {
            if(is_dir($this->fileDir) === false)
            {
                mkdir($this->fileDir);
            }
        }
    }

    protected function getAllApisInfo()
    {
        if( empty($this->allApiDetails) === true and
            file_exists($this->filePath) === true and
            $apiDetails = file_get_contents($this->filePath))
        {
            $this->allApiDetails = json_decode($apiDetails, true);
        }

        return $this->allApiDetails;
    }

}
