<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;

class Stakeholder implements EntityToProtoConvertorInterface
{
    protected StakeholderEntity $entity;

    use DeleteNotSupported;

    protected array $dirtyFieldKeys;

    function __construct(StakeholderEntity $entity, array $dirtyFieldKeys)
    {
        $this->entity = $entity;

        $this->dirtyFieldKeys = $dirtyFieldKeys;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $stakeholderSaveRequest = new MerchantV1\StakeholderSaveRequest();

        $rawAttributes = $this->entity->getAttributes();

        $stakeholder = new MerchantV1\Stakeholder;
        $stakeholder->setId(Helper::notNullCheck($rawAttributes, StakeholderEntity::ID));
        $stakeholder->setMerchantId(Helper::notNullCheck($rawAttributes, StakeholderEntity::MERCHANT_ID));
        $stakeholder->setEmail(Helper::converToStringValue($rawAttributes, StakeholderEntity::EMAIL));
        $stakeholder->setName(Helper::converToStringValue($rawAttributes, StakeholderEntity::NAME));
        $stakeholder->setPhonePrimary(Helper::converToStringValue($rawAttributes, StakeholderEntity::PHONE_PRIMARY));
        $stakeholder->setPhoneSecondary(Helper::converToStringValue($rawAttributes, StakeholderEntity::PHONE_SECONDARY));
        $stakeholder->setDirector(Helper::convertToInt32ValueFromBool($rawAttributes, StakeholderEntity::DIRECTOR));
        $stakeholder->setExecutive(Helper::convertToInt32ValueFromBool($rawAttributes, StakeholderEntity::EXECUTIVE));
        $stakeholder->setPercentageOwnership(Helper::convertToUInt32Value($rawAttributes, StakeholderEntity::PERCENTAGE_OWNERSHIP));
        $stakeholder->setPoiIdentificationNumber(Helper::converToStringValue($rawAttributes, StakeholderEntity::POI_IDENTIFICATION_NUMBER));
        $stakeholder->setPoiStatus(Helper::converToStringValue($rawAttributes, StakeholderEntity::POI_STATUS));
        $stakeholder->setPoaStatus(Helper::converToStringValue($rawAttributes, StakeholderEntity::POA_STATUS));
        $stakeholder->setNotes(Helper::converToStringValue($rawAttributes, StakeholderEntity::NOTES));
        $stakeholder->setPanDocStatus(Helper::converToStringValue($rawAttributes, StakeholderEntity::PAN_DOC_STATUS));
        $stakeholder->setAadhaarEsignStatus(Helper::converToStringValue($rawAttributes, StakeholderEntity::AADHAAR_ESIGN_STATUS));
        $stakeholder->setAadhaarPin(Helper::converToStringValue($rawAttributes, StakeholderEntity::AADHAAR_PIN));
        $stakeholder->setAadhaarLinked(Helper::convertToInt32ValueOrDefault($rawAttributes, StakeholderEntity::AADHAAR_LINKED, 1));
        $stakeholder->setAadhaarVerificationWithPanStatus(Helper::converToStringValue($rawAttributes, StakeholderEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS));
        $stakeholder->setBvsProbeId(Helper::converToStringValue($rawAttributes, StakeholderEntity::BVS_PROBE_ID));
        $stakeholder->setAuditId(Helper::converToStringValue($rawAttributes, StakeholderEntity::AUDIT_ID));
        $stakeholder->setVerificationMetadata(Helper::converToStringValue($rawAttributes, StakeholderEntity::VERIFICATION_METADATA));

        $stakeholderSaveRequest->setStakeholder($stakeholder);
        $stakeholderSaveRequest->setFields($this->dirtyFieldKeys);
        $saveRequest->setStakeholderSaveRequests([$stakeholderSaveRequest]);

        return $saveRequest;
    }
}
