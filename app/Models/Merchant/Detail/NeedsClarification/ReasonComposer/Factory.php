<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use APP;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as NcConstants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\RetryStatus as RetryStatus;

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
    public function getClarificationReasonComposer(array $needsClarificationMetaData, array $noDocData=[]): ClarificationReasonComposerInterface
    {
        $needClarificationVersion =
            $needsClarificationMetaData[NeedsClarificationMetaData::NEEDS_CLARIFICATION_VERSION] ?? '';

        switch ($needClarificationVersion)
        {
            case NeedsClarificationMetaData::VERSION_V2 :
                return $this->getNeedsClarificationReasonComposerForV2($needsClarificationMetaData, $noDocData);

            case NeedsClarificationMetaData::VERSION_V1:
                return $this->getNeedsClarificationReasonComposerForV1($needsClarificationMetaData, $noDocData);

            default :
                throw new LogicException(null, ErrorCode::INVALID_NEEDS_CLARIFICATION_VERSION, [
                    NeedsClarificationMetaData::NEEDS_CLARIFICATION_VERSION => $needClarificationVersion
                ]);
        }
    }

    protected function getValidationUnitFromNeedsClarificationMetadata(array $needsClarificationMetaData)
    {
        $fieldType = $needsClarificationMetaData[NcConstants::FIELD_TYPE] ?? '';
        $referenceKey = $needsClarificationMetaData[NeedsClarificationMetaData::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY] ?? [];

        switch ($fieldType)
        {
            case NcConstants::TEXT:
                return Constants::IDENTIFIER;
        }

        return Constant::FIELD_ARTEFACT_DETAILS_MAP[$referenceKey][Constant::VALIDATION_UNIT] ?? '';
    }

    /**
     * @param array $needsClarificationMetaData
     *
     * @return ClarificationReasonComposerInterface
     */
    protected function getNeedsClarificationReasonComposerForV2(array $needsClarificationMetaData, array $noDocData=[]): ClarificationReasonComposerInterface
    {
        $referenceKey = $needsClarificationMetaData[NeedsClarificationMetaData::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY] ?? [];
        $artefactType = Constant::FIELD_ARTEFACT_DETAILS_MAP[$referenceKey][Constant::ARTEFACT_TYPE] ?? '';

        $validationUnit = $this->getValidationUnitFromNeedsClarificationMetadata($needsClarificationMetaData);

        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerId(
            $this->merchantDetails->getMerchantId(),
            $artefactType,
            $validationUnit,
            Constant::MERCHANT
        );

        if ($this->merchantDetails->merchant->isNoDocOnboardingEnabled() === true and $this->shouldTriggerDedupeClarificationComposer($noDocData, $needsClarificationMetaData) === true)
        {
            return new DedupeClarificationReasonComposer($needsClarificationMetaData, $noDocData);
        }

        if((empty($validation) === true) and
            ($this->merchantDetails->merchant->isRouteNoDocKycEnabledForParentMerchant() === true) and
            ($artefactType === Constant::GSTIN))
        {
            return new GstinMissingReasonComposer($needsClarificationMetaData);
        }

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

        // trigger Auto NC for Route linked accounts for Spam detected error.
        if( ($this->merchantDetails->merchant->isLinkedAccount() === true) and
            ($this->merchantDetails->merchant->isRouteNoDocKycEnabledForParentMerchant() === true) and
            ($validation->getErrorCode() === Constants::SPAM_DETECTED_ERROR))
        {
            return new SpamDetectedReasonComposer($validation, $needsClarificationMetaData);
        }

        return new IncorrectDetailsReasonComposer($validation, $needsClarificationMetaData);
    }

    /**
     * @param array $needsClarificationMetaData
     * @return ClarificationReasonComposerInterface
     */
    protected function getNeedsClarificationReasonComposerForV1(array $needsClarificationMetaData, array $noDocData=[]): ClarificationReasonComposerInterface
    {
        if ($this->merchantDetails->merchant->isNoDocOnboardingEnabled() === true and $this->shouldTriggerDedupeClarificationComposer($noDocData, $needsClarificationMetaData) === true)
        {
            return new DedupeClarificationReasonComposer($needsClarificationMetaData, $noDocData);
        }

        return new BankAccountClarificationComposer($this->merchantDetails,$needsClarificationMetaData);
    }

    private function shouldTriggerDedupeClarificationComposer(array $noDocData, array $needsClarificationMetaData): bool
    {
        if (empty($noDocData) === false and isset($needsClarificationMetaData[DEConstants::DEDUPE_CHECK_KEY]) === true)
        {
            $fieldName = $needsClarificationMetaData[DEConstants::DEDUPE_CHECK_KEY];
            if (isset($noDocData[DEConstants::DEDUPE][$fieldName]) === true and $noDocData[DEConstants::DEDUPE][$fieldName][DEConstants::RETRY_COUNT] > 0 and $noDocData[DEConstants::DEDUPE][$fieldName][DEConstants::STATUS] === RetryStatus::PENDING)
            {
                return true;
            }
        }

        return false;
    }
}
