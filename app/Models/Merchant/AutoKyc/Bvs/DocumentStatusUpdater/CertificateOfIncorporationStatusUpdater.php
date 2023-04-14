<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */

class CertificateOfIncorporationStatusUpdater extends VerificationDetailStatusUpdater
{
    protected $entity;

    /**
     * DefaultStatusUpdate constructor.
     *
     * @param MerchantEntity $merchant
     * @param Detail\Entity  $merchantDetails
     * @param Entity         $consumedValidation
     * @param string         $entity
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                Entity $consumedValidation,
                                string $entity=E::MERCHANT_DETAIL)
    {
        parent::__construct($merchant,$merchantDetails, $consumedValidation,$entity);

        $this->verificationDetailArtefactType = $this->artefactType;

        $this->verificationDetailValidationUnit = MVD\Constants::DOC;
    }
}
