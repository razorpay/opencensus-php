<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use RZP\Models\Base;

abstract class ResponseProcessor extends Base\Core
{
    protected $transferMode;

    protected $data         = [];

    protected $reconEntity;

    const PAYMENT_REF_NO    = 'payment_ref_no';
    const UTR               = 'utr';
    const BANK_STATUS_CODE  = 'bank_status_code';
    const PAYMENT_DATE      = 'payment_date';
    const RRN               = 'rrn';
    const REFERENCE_NUMBER  = 'reference_number';

    public function __construct()
    {
        parent::__construct();
    }

    public function reconcile(array $response, string $mode)
    {
        $this->transferMode = $mode;

        $this->updateTransferStatus($response);
    }

    protected function fetchReconEntity(string $id)
    {
        $this->reconEntity = $this->repo
            ->fund_transfer_attempt
            ->findWithRelations($id, ['source']);
    }

    protected function updateTransferStatus(array $response)
    {
        $this->extractData($response);

        $this->updateReconEntity();

        $sourceBatchId = $this->reconEntity->source->getBatchFundTransferId();

        if ($sourceBatchId !== $this->reconEntity->getBatchFundTransferId())
        {
            return;
        }

        $this->updateSourceEntity();
    }

    protected function updateSourceEntity()
    {
        $utr = $this->reconEntity->getUtr();
        $remarks = $this->reconEntity->getRemarks();

        $this->reconEntity->source->setUtr($utr);
        $this->reconEntity->source->setRemarks($remarks);

        $this->repo->saveOrFail($this->reconEntity->source);
    }

    protected function getNullOnEmpty($value)
    {
        return (empty($value) === true) ? null : $value;
    }

    abstract protected function updateReconEntity();

    abstract protected function extractData(array $response);
}
