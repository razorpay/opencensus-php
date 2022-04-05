<?php

namespace RZP\Models\Batch\Processor;

use RZP\Exception;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Processor\Util\PartnersCommonUtil as PartnersCommonUtil;

class PartnerReferralFetch extends Base
{
    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        (new PartnersCommonUtil)->validateHeaders($rows, $delimiter, $this->inputFileType, $this->batch->getType());

        return parent::parseFirstRowAndGetHeadings($rows, $delimiter);
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }
}
