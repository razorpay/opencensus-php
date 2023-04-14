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
use RZP\Models\Merchant\AutoKyc\Bvs\Config\Gstin;

/**
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */
class GstCertificateOcrStatusUpdater extends VerificationDetailStatusUpdater
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

        $this->verificationDetailArtefactType=Constant::GSTIN;

        $this->verificationDetailValidationUnit = MVD\Constants::DOC;
    }
}
