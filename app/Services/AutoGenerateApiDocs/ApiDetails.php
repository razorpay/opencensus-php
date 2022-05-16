<?php

namespace  RZP\Services\AutoGenerateApiDocs;

class ApiDetails
{
    protected $description;
    protected $summary;

    protected $requestUrlWithVariable;
    protected $requestUrl;

    protected $requestMethod;
    protected $requestContentType;
    protected $requestDescription;
    protected $requestData;

    protected $responseContentType;
    protected $responseDescription;
    protected $responseData;
    protected $responseStatusCode;

    const API_RESPONSE_CODE_200 = 200;

    public function __construct(
        string  $requestUrl,
        string  $requestMethod,
        int     $responseStatusCode,
        array   $requestData = [],
        array   $responseData = [],
        string  $description = '',
        string  $summary    = '',
        ?string $requestUrlWithVariable = null,
        string  $requestContentType = Constants::CONTENT_TYPE_APPLICATION_JSON,
        string  $requestDescription = '',
        string  $responseContentType =  Constants::CONTENT_TYPE_APPLICATION_JSON,
        string  $responseDescription = ''
    )
    {
        $this->requestUrl               = $requestUrl;
        $this->requestMethod            = $requestMethod;
        $this->responseStatusCode       = $responseStatusCode;
        $this->requestData              = $requestData;
        $this->responseData             = $responseData;
        $this->description              = $description;
        $this->summary                  = $summary;
        $this->requestUrlWithVariable   = $requestUrlWithVariable ?? $requestUrl;
        $this->requestContentType       = $requestContentType;
        $this->requestDescription       = $requestDescription;
        $this->responseContentType      = $responseContentType;
        $this->responseDescription      = $responseDescription;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getRequestUrlWithVariable(): string
    {
        return $this->requestUrlWithVariable;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getRequestContentType(): string
    {
        return $this->requestContentType;
    }

    /**
     * @return string
     */
    public function getRequestDescription(): string
    {
        return $this->requestDescription;
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @return string
     */
    public function getResponseContentType(): string
    {
        return $this->responseContentType;
    }

    /**
     * @return string
     */
    public function getResponseDescription(): string
    {
        return $this->responseDescription;
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @return int
     */
    public function getResponseStatusCode(): int
    {
        return $this->responseStatusCode;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getApiDataUniqueIdentifier()
    {
        $hashData = array_merge(
            [
                $this->getRequestUrl(),
                $this->getRequestMethod(),
                $this->arrayKeysRecursiveAndUnique($this->getRequestData())
            ]);

        sort($hashData);

        return substr(md5(json_encode($hashData, true)), 0, 8); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto
    }

    private function arrayKeysRecursiveAndUnique(array $array): array
    {
        $index = [];

        foreach ($array as $key => $value)
        {
            if (is_array($value) === true)
            {
                $index[$key] = $this->arrayKeysRecursiveAndUnique($value);
            }
            else
            {
                $index[] = $key;
            }
        }

        return array_unique($index, SORT_REGULAR);
    }
}
