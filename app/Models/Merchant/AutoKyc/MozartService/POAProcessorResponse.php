<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use RZP\Models\Merchant\Detail\Constants;

class POAProcessorResponse extends BaseResponse
{

    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $extractedData = [
            Constants::NAME          => $this->getOcrName(),
            Constants::SUCCESS       => $this->isPOAVerifierResponseSuccess(),
            Constants::DOCUMENT_TYPE => $this->requestInput[Constants::DOCUMENT_TYPE] ?? "",
        ];

        return array_merge($data, $extractedData);
    }

    public function getOcrName()
    {
        $ocrName = $this->parseResponseAndGetNameFromOCR();

        return $ocrName;
    }

    public function isPOAVerifierResponseSuccess()
    {
        if (empty($this->responseBody) === true)
        {
            return false;
        }
        $response = flatten_array($this->responseBody);

        return (isset ($response['success']) and ($response['success'] === true)) and
               ((isset($response['data.content.response.statusCode']) === true) and
                ($response['data.content.response.statusCode'] === 101));
    }

    /**
     * Parses and extracts the name from OCR response
     *
     * @return string |null
     */
    private function parseResponseAndGetNameFromOCR()
    {
        $responseFromOCR = $this->responseBody;

        if ($this->isPOAVerifierResponseSuccess() === false)
        {
            return null;
        }

        $ocrResult = $responseFromOCR['data']['content']['response']['result'];

        foreach ($ocrResult as $res)
        {
            $flattenOcrResult = flatten_array($res);

            $ocrNameKey = $this->getOcrNameKeyByType($flattenOcrResult['type']);

            if (empty($flattenOcrResult[$ocrNameKey]) === false)
            {
                return $flattenOcrResult[$ocrNameKey];
            }
        }

        return null;
    }

    /**
     * For "Passport Front" the key is details.givenName.value
     * For "Aadhaar Front Bottom" and "Voterid Front" the key is details.name.value
     *
     * @param $type
     *
     * @return string
     */
    private function getOcrNameKeyByType($type)
    {
        switch ($type)
        {
            case stripos($type, 'Passport front'):
                $key = 'details.givenName.value';

                break;
            case stripos($type, 'Aadhaar front bottom'):
            case stripos($type, 'Voterid front'):
                $key = 'details.name.value';

                break;
            default:
                $key = '';
        }

        return $key;
    }
}
