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

    private function parseResponseAndGetNameFromOCR($responseFromOCR)
    {
        if (empty($responseFromOCR) === true)
        {
            return null;
        }

        $result = flatten_array($responseFromOCR);

        $key = 'data.content.response.result.0.details.name.value';

        // For "Passport Front" the key is
        // data.content.response.result.0.details.givenName.value
        // For "Aadhaar Front Bottom" and "Voterid Front" the key
        // is data.content.response.result.0.details.name.value

        if (isset($result['data.content.response.result.0.type']) === true)
        {
            $type = strtolower($result['data.content.response.result.0.type']);

            if (strpos($type, 'passport') !== false)
            {
                $key = 'data.content.response.result.0.details.givenName.value';
            }
        }

        if (isset($result[$key]) === true)
        {
            return $result[$key];
        }

        return null;
    }
}
