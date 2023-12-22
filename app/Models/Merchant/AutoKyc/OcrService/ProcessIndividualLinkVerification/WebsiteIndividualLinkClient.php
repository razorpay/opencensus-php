<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService\ProcessIndividualLinkVerification;

use RZP\Models\Merchant\AutoKyc\OcrService\BaseClient;

class WebsiteIndividualLinkClient extends BaseClient
{

    /**
     * Create a website verification job.
     *
     * @param array $payload
     * @return array|null
     */
    public function createWebsiteVerificationJob(array $payload): ?array
    {
        $url = $this->config['host'] . Constants::PROCESS_INDIVIDUAL_LINK_VERIFICATION_ENDPOINT;

        $response = $this->request( $url, 'POST', $payload);

        return $this->processResponse($response);
    }

    /**
     * Get the result of a website verification.
     *
     * @param array $payload
     * @return array|null
     */
    public function getWebsiteVerificationResult(array $payload): ?array
    {
        $url = $this->config['host'] . Constants::GET_WEBSITE_PATH;

        $response = $this->request( $url, 'POST', $payload);

        return $this->processResponse($response);
    }

    /**
     * Process the API response.
     *
     * @param object $response
     * @param string|null $key
     * @return array|null
     */
    private function processResponse($response, $key = null): ?array
    {
        if ($response->status_code === 200) {
            $responseBody = json_decode($response->body, true);
            return $responseBody;
        }

        return null;
    }
}
