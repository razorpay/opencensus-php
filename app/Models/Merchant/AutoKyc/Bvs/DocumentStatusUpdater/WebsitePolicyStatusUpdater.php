<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Website;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\BvsValidation\Entity as Validation;
use RZP\Models\Merchant\Detail\BusinessCategoriesV2\BusinessSubCategoryMetaData;

/**
 * Class DocumentStatusUpdater
 *
 * @package RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater
 */

class WebsitePolicyStatusUpdater extends VerificationDetailStatusUpdater
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

        $this->verificationDetailArtefactType = Constant::WEBSITE_POLICY;

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
            $subCategory = $this->merchantDetails->getBusinessSubcategory();

            $category = $this->merchantDetails->getBusinessCategory();

            if ($this->merchantDetails->getActivationFormMilestone() !== Detail\Constants::L2_SUBMISSION)
            {
                return Constants::INITIATED;
            }

            $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subCategory);

            $requiredPolicies = $subcategoryMetaData[BusinessSubCategoryMetaData::REQUIRED_WEBSITE_POLICIES];

            foreach ($requiredPolicies as $policy => $required)
            {
                $policyResult = $this->result[$policy]['analysis_result']['validation_result'] ?? null;

                if ($required === true and $policyResult !== true)
                {
                    return $this->getFailedStatus();
                }
            }

            return $this->getVerifiedStatus();
        }

        else if ($validation->getValidationStatus() === Constants::FAILED)
        {
            return $this->getFailedStatus();
        }

        //
        // If validation is still in pending status
        //

        return null;
    }
}
