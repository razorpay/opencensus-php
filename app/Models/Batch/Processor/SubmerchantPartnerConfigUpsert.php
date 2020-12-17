<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Processor\Util\PartnersCommonUtil as PartnersCommonUtil;

class SubmerchantPartnerConfigUpsert extends Base
{
    protected function parseFirstRowAndGetHeadings(array &$rows, string $delimiter)
    {
        (new PartnersCommonUtil)->validateHeaders($rows, $delimiter, $this->inputFileType, $this->batch->getType());

        return parent::parseFirstRowAndGetHeadings($rows, $delimiter);
    }
}