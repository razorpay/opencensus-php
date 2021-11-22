<?php

namespace RZP\Services\AutoGenerateApiDocs;

class ApiDetailToOpenApiSpecConverter
{

    protected $apiDetails;

    public function __construct(ApiDetails $apiDetails)
    {
        $this->apiDetails = $apiDetails;
    }

    public function convert()
    {
        $apiRequestResponseSpec = [
            'summary'     => $this->apiDetails->getSummary(),
            'description' => $this->apiDetails->getDescription(),
        ];

        $apiRequestResponseSpec['responses'] = $this->getResponseSpec();

        if ((in_array($this->apiDetails->getRequestMethod(), ['get' , 'delete']) === false) and
            (empty($this->apiDetails->getRequestData()) === false))
        {
            $apiRequestResponseSpec['requestBody'] = $this->getRequestSpec();
        }

        if( empty($urlSpec = $this->getUrlSpec()) === false)
        {
            $apiRequestResponseSpec['parameters'] = $urlSpec;
        }

        return  [ strtolower($this->apiDetails->getRequestMethod()) => $apiRequestResponseSpec ];
    }

    protected function isAssociativeArray(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    protected function getParamsSchema($input)
    {
        switch(gettype($input))
        {
            case 'string':
                return ['type' => 'string'];
            case 'integer':
                return ["type" => 'integer'];
            case 'boolean':
                return ['type' => 'boolean'];
            case 'array':
                $schema = [];
                if($this->isAssociativeArray($input) === false)
                {
                    $schema['type']  = 'array';
                    $schema['items'] = ['oneOf' => []];
                    $cacheType = [];
                    foreach ($input as $index => $value)
                    {
                        $tempSpec = $this->getParamsSchema($value);
                        if(in_array($tempSpec['type'], $cacheType) === false)
                        {
                            $cacheType[$tempSpec['type']] = true;
                            $schema['items']['oneOf'][] = $tempSpec;
                        }
                    }
                }else{
                    $schema['type']  = 'object';
                    $schema['properties'] = [];
                    foreach ($input as $keyName => $value)
                    {
                        $schema['properties'][$keyName] =  $this->getParamsSchema($value);
                    }
                }
                return $schema;

            default:
                // Null type is not supported in open api spec, using string default with nullable true
                return ['type' => 'string', 'nullable' => true];
        }
    }

    protected function getOpenApiContentSpec(string $contentType, array $inputData)
    {
        $schemaSpec = [ ];

        if (empty($inputData) === false )
        {
            $schemaSpec['schema'] = $this->getParamsSchema($inputData);

            $schemaSpec['schema']['example'] = $inputData;
        }

        return  [
                    $contentType => $schemaSpec
                ];
    }

    protected function getRequestSpec()
    {
        return [
                'description'   =>  $this->apiDetails->getRequestDescription(),
                'content'       => $this->getOpenApiContentSpec($this->apiDetails->getResponseContentType(), $this->apiDetails->getRequestData())
            ];
    }

    protected function getResponseSpec()
    {
        $responseSpec = [
            'description' => $this->apiDetails->getResponseDescription(),
        ];

        if(empty($this->apiDetails->getResponseData()) === false)
        {
            $responseSpec['content'] =   $this->getOpenApiContentSpec($this->apiDetails->getResponseContentType(), $this->apiDetails->getResponseData() );
        }

        return [
            $this->apiDetails->getResponseStatusCode() => $responseSpec
        ];
    }

    protected function getParamsAndExampleSpec(array $data)
    {
        $paramsInfo = $paramsExample = [];

        foreach ($data as $keyName => $value)
        {
            $paramsInfo = array_merge($paramsInfo, [$keyName => ['type' => gettype($keyName)]]);

            $paramsExample = array_merge($paramsExample, [$keyName =>  $value]);
        }

        return [$paramsInfo, $paramsExample];
    }

    protected function getSpecificationOfVariables($keyValueMap, $type)
    {
        $result = [];
        foreach ($keyValueMap as $key => $value)
        {
            $result[] = [
                'name' =>  $key,
                'in'   =>  $type,
                'required' => $type === 'path' ? true : false,
                'schema' => $this->getParamsSchema($value)
            ];
        }
        return $result;
    }

    protected function getVariablesInRequestUrl(string $requestUrlWithVariable, string $requestUrl)
    {
        $matches  = $params   = [];

        preg_match_all('"{([^}]*)}"' ,$requestUrlWithVariable, $matches);

        $requestUrlArr             = explode("/" , $requestUrl);

        $requestUrlWithVariableArr = explode("/", $requestUrlWithVariable);

        if (!empty($matches[0]))
        {
            foreach($matches[0] as $key)
            {
                $index = array_search($key, $requestUrlWithVariableArr);
                $params[substr($key,1 , -1 )] = $requestUrlArr[$index];
            }
        }

        return $params;
    }

    protected function getUrlSpec()
    {
        $requestUrlWithVariableArr = explode('?', $this->apiDetails->getRequestUrlWithVariable());

        $requestUrlArr             = explode('?', $this->apiDetails->getRequestUrl());

        $requestVariable           = $this->getVariablesInRequestUrl($requestUrlWithVariableArr[0], $requestUrlArr[0]);

        $urlSpec                   = $this->getSpecificationOfVariables($requestVariable , 'path');

        if(empty($requestUrlWithVariableArr[1]) === false)
        {
            $queryVariablesMapping   = [];
            parse_str($requestUrlWithVariableArr[1], $queryVariablesMapping);

            $urlSpec = array_merge(
                $urlSpec,
                $this->getSpecificationOfVariables($queryVariablesMapping , 'query')
            );
        }
        return $urlSpec;
    }

}
