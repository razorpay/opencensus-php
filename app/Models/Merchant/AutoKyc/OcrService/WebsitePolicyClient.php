<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

class WebsitePolicyClient extends BaseClient
{
    const PROCESS_VERIFICATION_PATH = '/website/api/v1/process_website_verification';

    const GET_WEBSITE_PATH = '/website/api/v1/get_website_details';

    public function createWebsitePolicyJob(array $payload)
    {
        $url = $this->config['host'] . self::PROCESS_VERIFICATION_PATH;

        $response = $this->request($url, 'POST', $payload);

        if ($response->status_code >= 200 and $response->status_code <= 299)
        {
            return json_decode($response->body, true);
        }

        return null;
    }

    public function getWebsitePolicyResult(array $payload): ?array
    {
        $url = $this->config['host'] . self::GET_WEBSITE_PATH;

        $response = $this->request($url, 'POST', $payload);

        if ($response->status_code >= 200 and $response->status_code <= 299)
        {
            $responseBody = json_decode($response->body, true);

            return $responseBody['result'] ?? null;
        }

        return null;
    }
}
