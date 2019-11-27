<?php

namespace RZP\Models\Merchant\Detail\Verifiers;

class PoaVerifierResponse
{
    /**
     * @var array
     */
    protected $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getOcrName()
    {
        $ocrName = $this->parseResponseAndGetNameFromOCR($this->response);

        return $ocrName;
    }

    /**
     * Parses and extracts the name from OCR response
     * @param $responseFromOCR
     * @return name |null
     */
    private function parseResponseAndGetNameFromOCR($responseFromOCR)
    {
        if (empty($responseFromOCR) === false and $this->isPOAVerifierResponseSuccess($responseFromOCR) === false)
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
     * @param $type
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

    private function isPOAVerifierResponseSuccess($responseFromOCR)
    {
        $response = flatten_array($responseFromOCR);
        return (isset ($response['success']) and ($response['success'] === true)) and
            ((isset($response['data.content.response.statusCode']) === true) and
                ($response['data.content.response.statusCode'] === 101));
    }
}
