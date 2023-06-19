<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class Factory
{
    public function getBvsRequestDispatcherForArtefact(
        string $artefact,
        Merchant\Entity $merchant,
        Detail\Entity $merchantDetails
    ): Base
    {
        switch ($artefact)
        {
            case Merchant\AutoKyc\Bvs\Constant::BANK_ACCOUNT:
                return new BankAccount($merchant, $merchantDetails);

            case Merchant\AutoKyc\Bvs\Constant::GSTIN:
                return new GstinAuth($merchant, $merchantDetails);

            case Merchant\AutoKyc\Bvs\Constant::CIN:
                return new CinAuth($merchant, $merchantDetails);

            case Merchant\AutoKyc\Bvs\Constant::LLPIN:
                return new LlpinAuth($merchant, $merchantDetails);

            case Merchant\AutoKyc\Bvs\Constant::PERSONAL_PAN:
                return new PersonalPan($merchant, $merchantDetails);

            case Merchant\AutoKyc\Bvs\Constant::BUSINESS_PAN:
                return new CompanyPan($merchant, $merchantDetails);

            default:
                throw new Exception\LogicException('artefact type not supported in this flow: ' . $artefact);
        }
    }

    public function getBvsRequestDispatcherForDocument(DocumentEntity $document, Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        $isExperimentEnabledForJoinValidation = (new Merchant\Core)->isRazorxExperimentEnable($merchant->getMerchantId(),
            RazorxTreatment::AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION);

        switch ($document->getDocumentType())
        {
            case Type::MSME_CERTIFICATE:
                return new MsmeDocOcr($merchant, $merchantDetails, $document);

            case Type::SHOP_ESTABLISHMENT_CERTIFICATE:
                return new ShopEstablishmentDocOcr($merchant, $merchantDetails, $document);

            case Type::AADHAR_BACK:
                if ($isExperimentEnabledForJoinValidation)
                {
                    return new AadhaarFrontAndBackValidationOcr($merchant, $merchantDetails, $document);
                }
                else
                {
                    return new AadharBackOcr($merchant, $merchantDetails, $document);
                }
            case Type::AADHAR_FRONT:
                if ($isExperimentEnabledForJoinValidation)
                {
                    return new AadhaarFrontAndBackValidationOcr($merchant, $merchantDetails, $document);
                }
                else
                {
                    throw new Exception\LogicException('document type not supported in this flow: ' .
                        $document->getDocumentType());
                }
            case Type::GST_CERTIFICATE:
                return new GSTCertificateOcr($merchant, $merchantDetails, $document);

            case Type::BUSINESS_PROOF_URL:

                switch ($merchantDetails->getBusinessType())
                {
                    case Detail\BusinessType::PARTNERSHIP:
                        return new PartnershipDeedOcr($merchant, $merchantDetails, $document);

                    case Detail\BusinessType::PRIVATE_LIMITED:
                    case Detail\BusinessType::PUBLIC_LIMITED:
                    case Detail\BusinessType::LLP:
                        return new CertificateOfIncorporationOcr($merchant, $merchantDetails, $document);

                    case Detail\BusinessType::NGO:
                    case Detail\BusinessType::TRUST:
                    case Detail\BusinessType::SOCIETY:
                        return new TrustSocietyNgoBusinessCertificateOcr($merchant, $merchantDetails, $document);
                }
                break;
            default:
                throw new Exception\LogicException('document type not supported in this flow: ' . $document->getDocumentType());
        }
    }

    public function getBvsRequestDispatchers(Merchant\Entity $merchant, Detail\Entity $merchantDetails, string $activationFormMilestone = ''): array
    {
        if ($activationFormMilestone === DetailConstants::L1_SUBMISSION)
        {
            return [
                new CompanyPan($merchant, $merchantDetails),
                new PersonalPan($merchant, $merchantDetails)
            ];
        }

        //
        // Linked accounts onboarded via the no doc KYC flow will have the `route_no_doc_kyc` feature flag assigned to
        // their parent merchant. These linked accounts need to go through bank account, PAN, GSTIN verifications. Other
        // linked accounts will go through only bank account verification even when PAN or GSTIN details are present.
        //
        if (($merchant->isLinkedAccount() === true) and
            ($merchant->parent->isRouteNoDocKycEnabled() === false))
        {
            return [
                new BankAccount($merchant, $merchantDetails),
            ];
        }

        return [
            new CompanyPanOcr($merchant, $merchantDetails),
            new PersonalPanOcr($merchant, $merchantDetails),
            new CancelledChequeOcr($merchant, $merchantDetails),
            new ShopEstablishmentAuth($merchant, $merchantDetails),
            new LlpinAuth($merchant, $merchantDetails),
            new CinAuth($merchant, $merchantDetails),
            new GstinAuth($merchant, $merchantDetails),
            new BankAccount($merchant, $merchantDetails),
            new CompanyPan($merchant, $merchantDetails),
            new PersonalPan($merchant, $merchantDetails)
        ];
    }

    public function getSyncBvsRequestDispatchers(Merchant\Entity $merchant, Detail\Entity $merchantDetails): array
    {
        // Linked Account activation is asynchronous and bvs validaitons should be triggered asynchronosly
        if($merchant->isLinkedAccount() === true)
        {
            return [];
        }

        if (($merchant->getOrgId() === OrgEntity::RAZORPAY_ORG_ID) and
            ($merchant->isNoDocOnboardingEnabled() === false) and
            ($merchant->isRouteNoDocKycEnabledForParentMerchant() === false))
        {
            return [
                new LlpinAuth($merchant, $merchantDetails),
                new CinAuth($merchant, $merchantDetails),
                new GstinAuth($merchant, $merchantDetails),
                new BankAccount($merchant, $merchantDetails),
                new CompanyPan($merchant, $merchantDetails),
                new PersonalPan($merchant, $merchantDetails)
            ];
        }
        else
        {
            if ($merchant->getOrgId() === OrgEntity::RAZORPAY_ORG_ID and $merchant->isNoDocOnboardingEnabled() === true)
            {
                return [
                    new LlpinAuth($merchant, $merchantDetails),
                    new CinAuth($merchant, $merchantDetails),
                    new GstinAuth($merchant, $merchantDetails),
                    new BankAccount($merchant, $merchantDetails),
                ];
            }
            else
            {
                return [];
            }
        }
    }
}
