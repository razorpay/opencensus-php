<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\BvsValidation\Entity as Validation;

/**
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */
class MccCategorisationStatusUpdater extends VerificationDetailStatusUpdater
{
    protected $verificationDetailArtefactType;

    /**
     * @var mixed|null
     */
    private mixed $result;

    private Entity $consumedValidation;

    /**
     * DefaultStatusUpdate constructor.
     *
     * @param MerchantEntity $merchant
     * @param Detail\Entity  $merchantDetails
     * @param Entity         $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant, $merchantDetails, $consumedValidation);

        $this->result = $consumedValidation->getMetadata();

        $this->consumedValidation = $consumedValidation;

        $this->verificationDetailArtefactType = $consumedValidation->getArtefactType();

        $this->verificationDetailValidationUnit = MVD\Constants::NUMBER;
    }

    public function getVerificationDetailsPayload($validation, $documentValidationStatus)
    {
        return array_merge(parent::getVerificationDetailsPayload($validation, $documentValidationStatus), [
            MVD\Entity::METADATA => $this->result
        ]);
    }

    public function getDocumentValidationStatus(Validation $validation): ?string
    {
        if ($validation->getValidationStatus() === Constants::SUCCESS)
        {
            $confidenceScore = $this->result[MVD\Constants::CONFIDENCE_SCORE] ?? null;

            if (empty($confidenceScore) === false and $confidenceScore >= MVD\Constants::MCC_CONFIDENCE_SCORE_THRESHOLD)
            {
                return $this->getVerifiedStatus();
            }
            else
            {
                return $this->getFailedStatus();
            }
        }
        else
        {
            if ($validation->getValidationStatus() === Constants::FAILED)
            {
                return $this->getFailedStatus();
            }
        }

        //
        // If validation is still in pending status
        //
        return null;
    }
}
