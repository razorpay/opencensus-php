<?php

namespace RZP\Services\AutoGenerateApiDocs;

class GenerateOpenApiSpecifications
{
    protected $allApisDetails               = [];

    protected $filesDir;

    protected $openApiSpecFileName;

    protected $openApiSpecFilePath;

    protected $combinedApiDetailsFilesPath;

    protected $openApiSpecification = [
        "openapi" => "3.0.3",
        "info" => [
            "description" => "This is a sample API doc server",
            "version" => "1.0.0",
            "title" => "API documentation"
        ],
        "servers" => [
            [
                "url" => "https://api-web.dev.razorpay.in/"
            ]
        ],
        "paths" => [

        ]
    ];

    public function __construct(string $combinedApiDetailsFilesPath, string $filesDir, string $openApiSpecFileName)
    {
        $this->filesDir                     = $filesDir;

        $this->openApiSpecFileName          = $openApiSpecFileName;

        $this->combinedApiDetailsFilesPath  = $combinedApiDetailsFilesPath;

        $this->openApiSpecFilePath          = $this->filesDir . $this->openApiSpecFileName;

        $this->allApisDetails               = json_decode(file_get_contents($this->combinedApiDetailsFilesPath), true);

        $this->initialiseFile();
    }

    protected function initialiseFile()
    {
        if(is_dir($this->filesDir) === false)
        {
            mkdir($this->filesDir);
        }
    }

    public function generate()
    {
        foreach($this->allApisDetails as $apiIdentifier => $apis)
        {
            $primaryApiDetails = null;

            $additionalApiDetails = [];

            $count = 0;

            foreach ($apis as $apiDataUniqueIdentifier => $apiDetails)
            {
                if($count >= Constants::INPUT_SET_API_LIMIT)
                {
                    break;
                }

                $count += 1;

                $apiDetailObj = unserialize($apiDetails); // nosemgrep : php.lang.security.unserialize-use.unserialize-use

                if ($primaryApiDetails === null)
                {
                    $primaryApiDetails = $apiDetailObj;
                }
                else
                {
                    $additionalApiDetails[] =$apiDetailObj;
                }
            }

            $this->getOpenSpecification($primaryApiDetails, $additionalApiDetails);
        }

        file_put_contents($this->openApiSpecFilePath, json_encode($this->openApiSpecification));
    }

    protected function getOpenSpecification(ApiDetails $primaryApiDetails, array $additionalApiDetails = [])
    {
        $this->openApiSpecification['paths'][explode('?', $primaryApiDetails->getRequestUrlWithVariable())[0]] = (new ApiDetailToOpenApiSpecConverter($primaryApiDetails, $additionalApiDetails))->convert();
    }

}
