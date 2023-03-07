<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

class MccCategorisationClientMock
{
    private $mockStatus;

    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }

    public function createCategorisationJob(array $payload): ?string
    {
        return match ($this->mockStatus)
        {
            'success' => 'LB6FunePO70FzC',
            default   => null,
        };
    }

    public function getCategorisation(array $payload): ?array
    {
        return match ($this->mockStatus)
        {
            'success' => [
                'website_result' => [
                    'category'          => 'financial_services',
                    'subcategory'       => 'trading',
                    'predicted_mcc'     => 6211,
                    'confidence_score'  => 0.81
                ]
            ],
            default   => null,
        };
    }
}
