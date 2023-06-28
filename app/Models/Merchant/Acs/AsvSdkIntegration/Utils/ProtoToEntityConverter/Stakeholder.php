<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;

class Stakeholder
{

    protected MerchantV1\Stakeholder $proto;

    function __construct(MerchantV1\Stakeholder $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): StakeholderEntity
    {
        $stakeholderRawAttributes = [
            StakeholderEntity::ID => $this->proto->getId(),
            StakeholderEntity::MERCHANT_ID => $this->proto->getMerchantId(),
            StakeholderEntity::NAME => $this->proto->getNameUnwrapped(),
            StakeholderEntity::EMAIL => $this->proto->getEmailUnwrapped(),
            StakeholderEntity::PHONE_PRIMARY => $this->proto->getPhonePrimaryUnwrapped(),
            StakeholderEntity::PHONE_SECONDARY => $this->proto->getPhoneSecondaryUnwrapped(),
            StakeholderEntity::DIRECTOR => $this->proto->getDirectorUnwrapped(),
            StakeholderEntity::EXECUTIVE => $this->proto->getExecutiveUnwrapped(),
            StakeholderEntity::PERCENTAGE_OWNERSHIP => $this->proto->getPercentageOwnershipUnwrapped(),
            StakeholderEntity::NOTES => $this->proto->getNotesUnwrapped(),
            StakeholderEntity::POI_IDENTIFICATION_NUMBER => $this->proto->getPoiIdentificationNumberUnwrapped(),
            StakeholderEntity::POI_STATUS => $this->proto->getPoiStatusUnwrapped(),
            StakeholderEntity::PAN_DOC_STATUS => $this->proto->getPanDocStatusUnwrapped(),
            StakeholderEntity::POA_STATUS => $this->proto->getPoaStatusUnwrapped(),
            StakeholderEntity::AADHAAR_ESIGN_STATUS => $this->proto->getAadhaarEsignStatusUnwrapped(),
            StakeholderEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS => $this->proto->getAadhaarVerificationWithPanStatusUnwrapped(),
            StakeholderEntity::AADHAAR_PIN => $this->proto->getAadhaarPinUnwrapped(),
            StakeholderEntity::AADHAAR_LINKED => $this->proto->getAadhaarLinkedUnwrapped(),
            StakeholderEntity::BVS_PROBE_ID => $this->proto->getBvsProbeIdUnwrapped(),
            StakeholderEntity::AUDIT_ID => $this->proto->getAuditIdUnwrapped(),
            StakeholderEntity::CREATED_AT => $this->proto->getCreatedAt(),
            StakeholderEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
            StakeholderEntity::VERIFICATION_METADATA => $this->proto->getVerificationMetadataUnwrapped(),
            StakeholderEntity::DELETED_AT => $this->proto->getDeletedAtUnwrapped(),
        ];

        $stakeholderEntity = new StakeholderEntity();
        $stakeholderEntity->setRawAttributes($stakeholderRawAttributes);
        $stakeholderEntity->exists = true;
        return $stakeholderEntity;
    }
}
