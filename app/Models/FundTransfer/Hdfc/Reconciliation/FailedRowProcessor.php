<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Hdfc\Constants;
use RZP\Models\FundTransfer\Hdfc\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

/**
 * Class FailedRowProcessor
 * @package RZP\Models\FundTransfer\Hdfc\Reconciliation
 *
 * This class is responsible for updating the failed reverse file contents.
 * Failed reverse files are files which ware failed to process from bink side
 * Reason can be invalid file format or inconsistent data in it
 */
class FailedRowProcessor extends BaseRowProcessor
{
    const PAYMENT_REF_NO        = 'payment_ref_no';
    const FAILURE_REASON        = 'failure_reason';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const NAME_WITH_BENE_BANK   = 'name_with_bene_bank';

    protected function processRow()
    {
        $error = substr($this->row[Headings::ERRORS], 0, 255);

        $this->parsedData = [
            self::PAYMENT_REF_NO        => $this->getNullOnEmpty(Headings::CUSTOMER_REFERENCE_NUMBER),
            self::FAILURE_REASON        => $error,
            self::NAME_WITH_BENE_BANK   => null,
        ];

        $this->reconEntityId = $this->parsedData[self::PAYMENT_REF_NO];

        $this->trace->info(TraceCode::FTA_RECON_PARSED_DATA, ['parsed_data' => $this->parsedData]);

    }

    /**
     * Updates the attempt data with the recon response
     */
    protected function updateReconEntity()
    {
        $this->reconEntity->setBankStatusCode(Status::FILE_ERROR);

        $this->reconEntity->setFailureReason($this->parsedData[self::FAILURE_REASON]);

        $this->reconEntity->saveOrFail();
    }


    /**
     * In this case UTR doesn't need to be updated.
     * This method will never be called.
     * @return null
     */
    protected function getUtrToUpdate()
    {
        return null;
    }
}
