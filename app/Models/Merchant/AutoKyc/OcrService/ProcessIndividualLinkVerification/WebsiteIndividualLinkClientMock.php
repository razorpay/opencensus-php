<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService\ProcessIndividualLinkVerification;

class WebsiteIndividualLinkClientMock
{
    private $mockStatus;
    
    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }
    
    public function createWebsiteVerificationJob(array $payload): ?array
    {
        return match ($this->mockStatus)
        {
            'success' => [
                'website_verification_id' => 'KiyvwAQlNX08Vv',
                'status'                  => 'initiated',
            ],
            default   => null,
        };
    }
    
    public function getWebsiteVerificationResult(array $payload): ?array
    {
        return match ($this->mockStatus)
        {
            'success' => [
                'website_verification_id' => 'KiyvwAQlNX08Vv',
                'status' => 'completed',
                'result' => [
                    'policy_details_file' => 'file_LSAa41ZugJ1BPj',
                    'terms' => [
                        'analysis_result' => [
                            'source' => 'automated',
                            'links_found' => ['https://www.sukhdev.org/termsofuse'],
                            'confidence_score' => 0.95775,
                            'relevant_details' => [],
                            'validation_result' => true
                        ]
                    ],
                    'refund' => [
                        'analysis_result' => [
                            'source' => 'automated',
                            'links_found' => ['https://www.sukhdev.org/refundpolicy'],
                            'confidence_score' => 0.9185,
                            'relevant_details' => [],
                            'validation_result' => true
                        ]
                    ],
                    'privacy' => [
                        'analysis_result' => [
                            'source' => 'automated',
                            'links_found' => ['https://www.sukhdev.org/privacypolicy'],
                            'confidence_score' => 0.9267197799682617,
                            'relevant_details' => [
                                'note' => 'Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,'
                            ],
                            'validation_result' => true
                        ]
                    ],
                    'contact_us' => [
                        'analysis_result' => [
                            'source' => 'automated',
                            'confidence_score' => 0.9467197799682617,
                            'links_found' => ['https://www.sukhdev.org/contactus'],
                            'relevant_details' => ['9987394065', 'sukhdevonline@gmail.com'],
                            'validation_result' => true
                        ]
                    ],
                    'shipping' => [
                        'analysis_result' => [
                            'source' => 'automated',
                            'confidence_score' => 0.9167197799682617,
                            'links_found' => ['https://www.sukhdev.org/contactus'],
                            'relevant_details' => ['9987394065', 'sukhdevonline@gmail.com'],
                            'validation_result' => true
                        ]
                    ],
                ]
            ],
            default   => null,
        };
    }
    
}