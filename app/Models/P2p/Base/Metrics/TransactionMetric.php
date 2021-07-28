<?php

namespace RZP\Models\P2p\Base\Metrics;

use RZP\Models\P2p\Transaction\Entity;

class TransactionMetric extends Metric
{
    // Name of the metric
    const PSP_TRANSACTION_TOTAL         = 'psp_transaction_total';

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
    const DIMENSION_PREVIOUS_STATUS     = 'previous_status';
    const DIMENSION_IS_SELF_TRANSFER    = 'is_self_transfer';

    protected $dimensions = [];

    public function __construct(Entity $transaction, array $original = null)
    {
        $this->dimensions = $this->getDimensions($transaction, $original);
    }

    public function pushCount()
    {
        $this->count(self::PSP_TRANSACTION_TOTAL, $this->dimensions);

        return $this;
    }

    /**
     * @param Entity $transaction
     * @param array|null $original
     * @return array
     */
    protected function getDimensions(Entity $transaction, array $original = null): array
    {
        $original = $original ?? [];

        $dimensions = [
            self::DIMENSION_MODE                => $transaction->getMode(),
            self::DIMENSION_TYPE                => $transaction->getType(),
            self::DIMENSION_FLOW                => $transaction->getFlow(),
            self::DIMENSION_STATUS              => $transaction->getStatus(),
            self::DIMENSION_GATEWAY             => $transaction->getGateway(),
            self::DIMENSION_ERROR_CODE          => $transaction->getErrorCode(),
            self::DIMENSION_PAYER_TYPE          => $transaction->getPayerType(),
            self::DIMENSION_PAYEE_TYPE          => $transaction->getPayeeType(),
            self::DIMENSION_MERCHANT_ID         => $transaction->getMerchantId(),
            self::DIMENSION_PREVIOUS_STATUS     => $original[Entity::STATUS] ?? null,
            self::DIMENSION_IS_SELF_TRANSFER    => $transaction->isSelfTransfer(),
        ];

        return $dimensions;
    }
}
