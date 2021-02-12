<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant\Validator as MerchantValidator;
use RZP\Models\Batch\Processor\Util\PartnersCommonUtil as PartnersCommonUtil;

class PartnerSubmerchantInvite extends Base
{
    protected function parseFirstRowAndGetHeadings(array &$rows, string $delimiter)
    {
        (new PartnersCommonUtil)->validateHeaders($rows, $delimiter, $this->inputFileType, $this->batch->getType());

        return parent::parseFirstRowAndGetHeadings($rows, $delimiter);
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }

    public function addSettingsIfRequired(& $input)
    {
        $product = $input['config']['product'] ?? null;

        // validate the product passed in the input params
        (new MerchantValidator())->validateMerchantProduct($product);
    }
}
