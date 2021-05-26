<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Document\Type;

class Factory
{
    public function getBvsRequestDispatcherForArtefact(
        string $artefact, Merchant\Entity $merchant, Detail\Entity $merchantDetails): Base
    {
        switch ($artefact)
        {
            case Merchant\AutoKyc\Bvs\Constant::BANK_ACCOUNT:

                return new BankAccount($merchant, $merchantDetails);

            default:

                throw new Exception\LogicException('artefact type not supported in this flow: '. $artefact);
        }
    }

    public function getBvsRequestDispatcherForDocument($documentType, Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        switch ($documentType)
        {
            case Type::MSME_CERTIFICATE:
                return new MsmeDocOcr($merchant, $merchantDetails);
            default:
                throw new Exception\LogicException('document type not supported in this flow: '. $documentType);
        }
    }

    public function getBvsRequestDispatchers(Merchant\Entity $merchant, Detail\Entity $merchantDetails): array
    {
        return [
            new CompanyPanOcr($merchant, $merchantDetails),
            new PersonalPanOcr($merchant, $merchantDetails),
            new CancelledChequeOcr($merchant, $merchantDetails),
            new ShopEstablishmentAuth($merchant, $merchantDetails),
            new GstinAuth($merchant, $merchantDetails),
            new LlpinAuth($merchant, $merchantDetails),
            new CinAuth($merchant, $merchantDetails),
            new BankAccount($merchant, $merchantDetails),
        ];
    }
}
