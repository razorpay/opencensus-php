<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

class WebsitePolicyClientMock
{
    private $mockStatus;

    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }

    public function createWebsitePolicyJob(array $payload)
    {
        return match ($this->mockStatus)
        {
            'success' => [
                'website_verification_id'   => 'LB6DS0KgCW0AFO',
                'status'                    => 'initiated',
            ],
            default   => null,
        };
    }

    public function getWebsitePolicyResult(array $payload): ?array
    {
        return match ($this->mockStatus)
        {
            'success' => $this->getSuccessResponse(),
            default   => null,
        };
    }

    private function getSuccessResponse()
    {
        return [
            "terms" => [
                "analysis_result" => [
                    "links_found" => [
                        "https://www.sukhdev.org/termsofuse"
                    ],
                    "confidence_score" => 0.5775,
                    "relevant_details" => [
                    ],
                    "validation_result" => true
                ]
            ],
            "refund" => [
                "analysis_result" => [
                    "links_found" => [
                        "https://www.sukhdev.org/refundpolicy"
                    ],
                    "confidence_score" => 0.6185,
                    "relevant_details" => [
                    ],
                    "validation_result" => true
                ]
            ],
            "privacy" => [
                "analysis_result" => [
                    "links_found" => [
                        "https://www.sukhdev.org/privacypolicy"
                    ],
                    "confidence_score" => 0.70671977996826,
                    "relevant_details" => [
                        "note" => "Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,"
                    ],
                    "validation_result" => true
                ]
            ],
            "contact_us" => [
                "analysis_result" => [
                    "links_found" => [
                        "https://www.sukhdev.org/contactus"
                    ],
                    "relevant_details" => [
                        "9987394065",
                        "sukhdevonline@gmail.com"
                    ],
                    "validation_result" => true
                ]
            ]
        ];
    }
}
