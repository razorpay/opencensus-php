<?php

namespace RZP\Exception;

/**
 * Exception raised when the TransferRecon job fails to update the settlementId
 * for one or more transactionIds.
 */
class SettlementIdUpdateException extends BaseException
{
    protected array $failedTransactionIds;

    protected bool $retrySameJob;

    public function __construct($failedTransactionIds, $retrySameJob)
    {
        $this->failedTransactionIds = $failedTransactionIds;

        $this->retrySameJob = $retrySameJob;

        parent::__construct('');
    }

    public function shouldRetrySameJob(): bool
    {
        return ($this->retrySameJob === true) and (empty($this->failedTransactionIds) === false);
    }

    public function getFailedTransactionIds(): array
    {
        return $this->failedTransactionIds;
    }
}
