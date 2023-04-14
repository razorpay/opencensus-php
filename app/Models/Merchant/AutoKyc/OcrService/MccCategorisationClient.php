<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

class MccCategorisationClient extends BaseClient
{
    const CATEGORISE_MCC_PATH = '/mcc/api/v1/categorise';

    const GET_MCC_PATH = '/mcc/api/v1/get_mcc_details';

    public function createCategorisationJob(array $payload)
    {
        $url = $this->config['host'] . self::CATEGORISE_MCC_PATH;

        $response = $this->request($url, 'POST', $payload);

        if ($response->status_code >= 200 and $response->status_code <= 299)
        {
            return json_decode($response->body, true);
        }

        return null;
    }

    public function getCategorisation(array $payload): ?array
    {
        $url = $this->config['host'] . self::GET_MCC_PATH;

        $response = $this->request($url, 'POST', $payload);

        if ($response->status_code >= 200 and $response->status_code <= 299)
        {
            $responseBody = json_decode($response->body, true);

            return $responseBody['category_result'] ?? null;
        }

        return null;
    }
}
