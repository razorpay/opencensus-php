<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class ShopEstablishmentDocOcr extends Base
{
    protected $document;

    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, DocumentEntity $document)
    {
        $this->document = $document;

        parent::__construct($merchant, $merchantDetails);
    }

    protected const SHOP_ESTABLISHMENT_DOC_INDEX = '1';

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return (($this->merchantDetails->getBusinessType() === Merchant\Detail\BusinessType::PROPRIETORSHIP) and
                ($this->document->getPublicFileStoreId() !== null));
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        return [
            Constant::ARTEFACT_TYPE   => Constant::SHOP_ESTABLISHMENT,
            Constant::CONFIG_NAME     => Constant::SHOP_ESTABLISHMENT_OCR,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::SHOP_ENTITY_NAME    => $this->merchantDetails->getBusinessName(),
                Constant::SHOP_OWNER_NAME     => $this->merchantDetails->getPromoterPanName(),
            ],
            Constant::PROOFS          => [
                self::SHOP_ESTABLISHMENT_DOC_INDEX => [
                    Constant::UFH_FILE_ID => $this->document->getPublicFileStoreId(),
                ],
            ],
        ];
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $input = [
            MVD\Entity::MERCHANT_ID          => $this->merchant->getId(),
            MVD\Entity::ARTEFACT_TYPE        => MVD\Constants::SHOP_ESTABLISHMENT,
            MVD\Entity::ARTEFACT_IDENTIFIER  => MVD\Constants::DOC,
            MVD\Entity::STATUS               => BvsValidationConstants::INITIATED
        ];

        (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);
    }
}
