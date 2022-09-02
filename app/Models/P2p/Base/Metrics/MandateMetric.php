<?php

namespace RZP\Models\P2p\Base\Metrics;

use RZP\Models\P2p\Mandate\Entity;

class MandateMetric extends Metric
{
    // Name of the metric
    const PSP_MANDATE_TOTAL            = 'psp_mandate_total';

    // DIMENSIONS
    const DIMENSION_MODE                = 'mode';
    const DIMENSION_TYPE                = 'type';
    const DIMENSION_FLOW                = 'flow';
    const DIMENSION_GATEWAY             = 'gateway';
    const DIMENSION_PAYEE_TYPE          = 'payee_type';
    const DIMENSION_PAYER_TYPE          = 'payer_type';
    const DIMENSION_ERROR_CODE          = 'error_code';
    const DIMENSION_MERCHANT_ID         = 'merchant_id';
    const DIMENSION_STATUS              = 'status';

    protected $dimensions = [];

    public function __construct(Entity $transaction, array $original = null)
    {
        $this->dimensions = $this->getDimensions($transaction, $original);
    }

    public function pushCount()
    {
        $this->count(self::PSP_MANDATE_TOTAL, $this->dimensions);

        return $this;
    }

    /**
     * @param Entity     $mandate
     * @param array|null $original
     *
     * @return array
     */
    protected function getDimensions(Entity $mandate, array $original = null): array
    {
        $dimensions = [
            self::DIMENSION_MODE             => $mandate->getMode(),
            self::DIMENSION_TYPE             => $mandate->getType(),
            self::DIMENSION_FLOW             => $mandate->getFlow(),
            self::DIMENSION_STATUS           => $mandate->getStatus(),
            self::DIMENSION_GATEWAY          => $mandate->getGateway(),
            self::DIMENSION_ERROR_CODE       => $mandate->getErrorCode(),
            self::DIMENSION_PAYER_TYPE       => $mandate->getPayerType(),
            self::DIMENSION_PAYEE_TYPE       => $mandate->getPayeeType(),
            self::DIMENSION_MERCHANT_ID      => $mandate->getMerchantId(),
        ];

        return $dimensions;
    }
}
