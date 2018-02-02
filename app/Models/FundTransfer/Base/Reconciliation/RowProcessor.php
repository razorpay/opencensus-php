<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

abstract class RowProcessor extends Base\Core
{
    protected $row;

    protected $version;

    protected $parsedData;

    /**
     * Entity corresponding to the payment_ref_no column in the file
     */
    protected $reconEntity;

    abstract protected function parseRow();

    abstract protected function updateReconEntity();

    public function __construct($row)
    {
        parent::__construct();

        $this->row = $row;
    }

    public function process()
    {
        $this->parseRow();

        $this->fetchEntities();

        if (empty($this->reconEntity) === true)
        {
            $this->trace->error(TraceCode::SETTLEMENT_RECONCILIATION_SKIPPED,
                [
                    'row'           => $this->row,
                    'parsed_data'   => $this->parsedData,
                ]);

            return null;
        }

        $this->updateEntities();

        return $this->reconEntity;
    }

    protected function fetchEntities()
    {
        $this->reconEntity = $this->repo
                                  ->fund_transfer_attempt
                                  ->findWithRelations($this->reconEntityId, ['source']);
    }

    protected function updateEntities()
    {
        $this->updateReconEntity();

        $this->updateSourceEntity();
    }

    protected function updateSourceEntity()
    {
        $utr = $this->reconEntity->getUtr();

        $this->reconEntity->source->setUtr($utr);

        $this->repo->saveOrFail($this->reconEntity->source);
    }
}
