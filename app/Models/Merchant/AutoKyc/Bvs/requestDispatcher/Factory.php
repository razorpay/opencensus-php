<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;

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

            default:
                throw new Exception\LogicException('artefact type not supported in this flow: ' . $artefact);
        }
    }

    public function getBvsRequestDispatcherForDocument(DocumentEntity $document, Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        switch ($document->getDocumentType())
        {
            case Type::MSME_CERTIFICATE:
                return new MsmeDocOcr($merchant, $merchantDetails, $document);

            case Type::SHOP_ESTABLISHMENT_CERTIFICATE:
                return new ShopEstablishmentDocOcr($merchant, $merchantDetails, $document);

            case Type::AADHAR_BACK:
                return new AadharBackOcr($merchant, $merchantDetails, $document);

            case Type::GST_CERTIFICATE:
                return new GSTCertificateOcr($merchant, $merchantDetails, $document);

            case Type::BUSINESS_PROOF_URL:

                switch ($merchantDetails->getBusinessType())
                {
                    case Detail\BusinessType::PARTNERSHIP:
                        return new PartnershipDeedOcr($merchant, $merchantDetails, $document);
                }
                break;

            default:
                throw new Exception\LogicException('document type not supported in this flow: ' . $document->getDocumentType());
        }
    }

    public function getBvsRequestDispatchers(Merchant\Entity $merchant, Detail\Entity $merchantDetails, string $activationFormMilestone = ''): array
    {
        if ($activationFormMilestone === DetailConstants::L1_SUBMISSION) {
            return [
                new CompanyPan($merchant, $merchantDetails),
                new PersonalPan($merchant, $merchantDetails)
            ];
        }

        // For Linked accounts, we only validate Bank Account details with BVS.
        // Other validations are not required.
        if ($merchant->isLinkedAccount() === true)
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
            new GstinAuth($merchant, $merchantDetails),
            new LlpinAuth($merchant, $merchantDetails),
            new CinAuth($merchant, $merchantDetails),
            new BankAccount($merchant, $merchantDetails),
            new CompanyPan($merchant, $merchantDetails),
            new PersonalPan($merchant, $merchantDetails)
        ];
    }
}
