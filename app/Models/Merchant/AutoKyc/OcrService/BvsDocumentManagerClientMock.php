<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use Platform\Bvs\Kycdocumentmanager\V1 as DocManagerV1;
use RZP\Models\Merchant\Detail\Constants as DEConstants;


class BvsDocumentManagerClientMock
{
    private $mockStatus;

    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }

    public function createDocumentRecord(array $payload)
    {
        $response = new DocManagerV1\DocumentRecordResponse();

        $response->setId("L8Jf6PJKzwakT7");

        $response->setStatus(DEConstants::INITIATED);

        return match ($this->mockStatus)
        {
            'success' => $response,
            default   => null,
        };
    }

    public function getDocumentRecord($request): DocManagerV1\DocumentRecordResponse
    {
        return match ($this->mockStatus)
        {
            'success' => $this->getSuccessResponse(),
            default   => null,
        };
    }

    private function getSuccessResponse(): DocManagerV1\DocumentRecordResponse
    {

        $response = new DocManagerV1\DocumentRecordResponse();

        $response->setId('L6UHKzobCSyI7U');

        $response->setStatus('success');

        $documentDetails = [
            "result" => [
                "prohibited" => [
                    "drugs" => [
                        "Phrases" => [
                            "cannabis" => 60,
                            "cbd" => 1,
                            "weed" => 1
                        ],
                        "total_count" => 62,
                        "unique_count" => 3
                    ],
                    "financial services" => [
                        "Phrases" => [
                            "investment" => 2
                        ],
                        "total_count" => 2,
                        "unique_count" => 1
                    ],
                    "miscellaneous" => [
                        "Phrases" => [
                            "cash" => 2,
                            "cigarette" => 1,
                            "cigarettes" => 2,
                            "fast" => 8,
                            "hazardous chemicals" => 1,
                            "money" => 7,
                            "payment methods" => 1,
                            "rapid" => 1,
                            "supplement" => 14,
                            "supplements" => 4,
                            "thc" => 1
                        ],
                        "total_count" => 42,
                        "unique_count" => 11
                    ],
                    "pharma" => [
                        "Phrases" => [
                            "alcohol" => 8,
                            "cannabinoid" => 37,
                            "codeine" => 1,
                            "hemp" => 1,
                            "marijuana" => 31,
                            "pain" => 1,
                            "pharmaceutical" => 5,
                            "prescription" => 20,
                            "topical" => 19,
                            "valium" => 1
                        ],
                        "total_count" => 124,
                        "unique_count" => 10
                    ],
                    "tobacco products" => [
                        "Phrases" => [
                            "tobacco" => 1
                        ],
                        "total_count" => 1,
                        "unique_count" => 1
                    ],
                    "travel" => [
                        "Phrases" => [
                            "booking" => 2,
                            "travel" => 2
                        ],
                        "total_count" => 4,
                        "unique_count" => 2
                    ]
                ],
                "required" => [
                    "policy disclosure" => [
                        "Phrases" => [
                            "cancellations" => 1,
                            "claims" => 11,
                            "contact us" => 1,
                            "delivery" => 46,
                            "payment" => 4,
                            "payments" => 2,
                            "privacy" => 5,
                            "privacy policy" => 8,
                            "refund" => 6,
                            "refund policy" => 4,
                            "refunds" => 1,
                            "return" => 3,
                            "return policy" => 2,
                            "returns" => 3,
                            "terms of service" => 1
                        ],
                        "total_count" => 98,
                        "unique_count" => 15
                    ],
                ],
            ],
            "website_url" => "https://www.hempstrol.com"
        ];

        $response->setDocumentDetails(get_Protobuf_Struct($documentDetails));

        return $response;
    }

}
