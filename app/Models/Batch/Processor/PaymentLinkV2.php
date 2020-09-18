<?php


namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Feature;
use RZP\Models\Batch\Entity;

class PaymentLinkV2 extends Base
{
    public function __construct(Entity $batch)
    {
        parent::__construct($batch);
    }

    protected function updateBatchHeadersIfApplicable(array &$headers, array $entries)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::PL_FIRST_MIN_AMOUNT) === true)
        {
            $headers[] = Batch\Header::FIRST_PAYMENT_MIN_AMOUNT;
        }

        $entry = current($entries);

        if ((empty($entry) === false) and (array_key_exists(Batch\Header::CURRENCY, $entry) === true))
        {
            // Inserting just before amount in paise thingy
            array_splice($headers, 4, 0, Batch\Header::CURRENCY);
        }

        if ((empty($entry) === false) and (array_key_exists(Batch\Header::PL_V2_UPI_LINK, $entry) === true))
        {
            // Inserting just before amount in paise thingy
            array_splice($headers, 4, 0, Batch\Header::PL_V2_UPI_LINK);
        }
    }
}
