<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Null status update implementation for a artefact type, ideally class name
 *
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */
class NullStatusUpdater extends BaseStatusUpdater
{
    /**
     * DefaultStatusUpdate constructor.
     *
     * @param MerchantEntity $merchant
     * @param Entity         $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant, $consumedValidation);
    }

    public function updateValidationStatus(): void
    {

    }

    public function updateStatusToPending(): void
    {

    }
}
