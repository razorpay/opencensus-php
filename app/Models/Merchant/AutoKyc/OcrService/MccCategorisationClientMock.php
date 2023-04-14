<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

class MccCategorisationClientMock
{
    private $mockStatus;

    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }

    public function createCategorisationJob(array $payload)
    {
        return match ($this->mockStatus)
        {
            'success' => [
                'id'     => 'LGjQP2ZQxa02ms',
                'status' => 'initiated',
            ],
            default   => null,
        };
    }

    public function getCategorisation(array $payload): ?array
    {
        return match ($this->mockStatus)
        {
            'success' => [
                'id' => 'LGjQP2ZQxa02ms',
                'status' => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'category'          => 'financial_services',
                        'subcategory'       => 'trading',
                        'predicted_mcc'     => 6211,
                        'confidence_score'  => 0.81,
                        'status'            => 'completed'
                    ]
                ]
            ],
            default   => null,
        };
    }
}
