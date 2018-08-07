<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\V3;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

class RowProcessor extends Base\RowProcessor
{
    protected $source = null;

    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Attempt\Version::V3;
    }

    public static function isV3($row): bool
    {
        if (($row[Headings::PAYMENT_DETAILS_3] !== null) and
            ($row[Headings::PAYMENT_DETAILS_3] === Attempt\Version::V3))
        {
            return true;
        }
        else if (($row[Headings::PAYMENT_DETAILS_4] !== null) and
            ($row[Headings::PAYMENT_DETAILS_4] === Attempt\Version::V3))
        {
            //
            // This condition is added as a workaround for a bug at Kotak's end.
            // The bug is that Kotak doesn't read the details sent under PAYMENT_DETAILS_4.
            // Also it tracks -
            //      PAYMENT_DETAILS_1 as PAYMENT_DETAILS_2
            //      PAYMENT_DETAILS_2 as PAYMENT_DETAILS_3
            //      PAYMENT_DETAILS_3 as PAYMENT_DETAILS_4
            // Hence even though we send version information in PAYMENT_DETAILS_3,
            // we are trying to read it from PAYMENT_DETAILS_4 here.
            //
            return true;
        }

        return false;
    }

    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();
        $this->reconEntity->setRemarks($this->parsedData['remarks']);
        $this->reconEntity->setBankStatusCode($this->parsedData['bank_status_code']);
        $this->reconEntity->setDateTime($this->parsedData['date_time']);
        $this->reconEntity->setCmsRefNo($this->parsedData['cms_ref_no']);

        $this->reconEntity->saveOrFail();
    }
}
