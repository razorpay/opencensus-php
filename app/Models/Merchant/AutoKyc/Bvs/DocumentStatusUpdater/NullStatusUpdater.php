<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use Rzp\Obs\Verification\V1\Detail;
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
     * @param MerchantEntity                     $merchant
     * @param \RZP\Models\Merchant\Detail\Entity $merchantDetails
     * @param Entity                             $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                \RZP\Models\Merchant\Detail\Entity $merchantDetails,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant, $merchantDetails,$consumedValidation);
    }

    public function updateValidationStatus(): void
    {

    }

    public function updateStatusToPending(): void
    {

    }
}
