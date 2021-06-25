<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use APP;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;

class Factory
{
    protected $merchantDetails;

    protected $repo;

    public function __construct(Entity $merchantDetails)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->merchantDetails = $merchantDetails;
    }

    /**
     * @param array $needsClarificationMetaData
     *
     * @return ClarificationReasonComposerInterface
     * @throws LogicException
     */
    public function getClarificationReasonComposer(array $needsClarificationMetaData): ClarificationReasonComposerInterface
    {
        $needClarificationVersion =
            $needsClarificationMetaData[NeedsClarificationMetaData::NEEDS_CLARIFICATION_VERSION] ?? '';

        switch ($needClarificationVersion)
        {
            case NeedsClarificationMetaData::VERSION_V2 :
                return $this->getNeedsClarificationReasonComposerForV2($needsClarificationMetaData);

            case NeedsClarificationMetaData::VERSION_V1:
                return $this->getNeedsClarificationReasonComposerForV1($needsClarificationMetaData);

            default :
                throw new LogicException(null, ErrorCode::INVALID_NEEDS_CLARIFICATION_VERSION, [
                    NeedsClarificationMetaData::NEEDS_CLARIFICATION_VERSION => $needClarificationVersion
                ]);
        }
    }

    /**
     * @param array $needsClarificationMetaData
     *
     * @return ClarificationReasonComposerInterface
     */
    protected function getNeedsClarificationReasonComposerForV2(array $needsClarificationMetaData): ClarificationReasonComposerInterface
    {
        $referenceKey = $needsClarificationMetaData[NeedsClarificationMetaData::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY] ?? [];

        $artefactType = Constant::FIELD_ARTEFACT_DETAILS_MAP[$referenceKey][Constant::ARTEFACT_TYPE] ?? '';

        $validationUnit = Constant::FIELD_ARTEFACT_DETAILS_MAP[$referenceKey][Constant::VALIDATION_UNIT] ?? '';

        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantDetails->getMerchantId(),
            $artefactType,
            $validationUnit,
            Constant::MERCHANT
        );

        if (empty($validation) === true)
        {
            return new DefaultClarificationReasonComposer();
        }

        $isNeedClarificationNotMatchedEnabled = (new MerchantCore())->isRazorxExperimentEnable(
            $this->merchantDetails->getId(),
            RazorxTreatment::SYSTEM_BASED_NEEDS_CLARIFICATION_NOT_MATCHED);

        if ($validation->getErrorCode() === Constants::RULE_EXECUTION_FAILED and
            $isNeedClarificationNotMatchedEnabled === true)
        {
            return new NotMatchedReasonComposer($validation, $needsClarificationMetaData);
        }

        // remove this once razorx experiment is ramped to 100.
        if ($validation->getErrorCode() === Constants::RULE_EXECUTION_FAILED)
        {
            return new DefaultClarificationReasonComposer();
        }

        return new IncorrectDetailsReasonComposer($validation, $needsClarificationMetaData);
    }

    /**
     * @param array $needsClarificationMetaData
     * @return ClarificationReasonComposerInterface
     */
    protected function getNeedsClarificationReasonComposerForV1(array $needsClarificationMetaData): ClarificationReasonComposerInterface
    {
        return new BankAccountClarificationComposer($this->merchantDetails,$needsClarificationMetaData);
    }
}
