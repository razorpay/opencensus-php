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
            foreach ($apis as $apiDataUniqueIdentifier => $apiDetails)
            {
                $this->getOpenSpecification(unserialize($apiDetails));
                //@todo continue loop with any of, for now skipping it
                continue;
            }
        }

        file_put_contents($this->openApiSpecFilePath, json_encode($this->openApiSpecification));
    }

    protected function getOpenSpecification(ApiDetails $apiDetails)
    {
        $this->openApiSpecification['paths'][explode('?', $apiDetails->getRequestUrlWithVariable())[0]] = (new ApiDetailToOpenApiSpecConverter($apiDetails))->convert();
    }

}
