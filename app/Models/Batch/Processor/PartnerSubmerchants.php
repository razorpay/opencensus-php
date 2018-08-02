<?php

namespace RZP\Models\Batch\Processor;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Exception\BadRequestException;

class PartnerSubmerchants extends Base
{
    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantCore = new Merchant\Core;
    }

    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $partnerType   = $entry[Header::PARTNER_TYPE];
        $submerchantId = $entry[Header::SUBMERCHANT_ID];
        $partnerId     = $entry[Header::PARTNER_MERCHANT_ID];

        $partner = $this->markAsPartner($entry, $partnerId, $partnerType);

        $this->mapSubmerchant($entry, $partner, $submerchantId);

        $entry[Header::STATUS] = Status::SUCCESS;
    }

    /**
     * @param array           $entry
     * @param Merchant\Entity $partner
     * @param                 $submerchantId
     *
     * @throws BadRequestException
     */
    protected function mapSubmerchant(array & $entry, Merchant\Entity $partner, $submerchantId)
    {
        if (empty($submerchantId) === true)
        {
            return;
        }

        // Using findOrFail here will not give a proper error code in the batch output.
        $submerchant = $this->repo->merchant->find($submerchantId);

        if ($submerchant === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Merchant\Entity::ID,
                [
                    Merchant\Entity::ID => $submerchantId
                ]);
        }

        $this->merchantCore->createPartnerSubmerchantAccessMap($partner, $submerchant);
    }

    /**
     * @param array $entry
     * @param       $merchantId
     * @param       $partnerType
     *
     * @return Merchant\Entity
     * @throws BadRequestException
     */
    protected function markAsPartner(array & $entry, $merchantId, $partnerType): Merchant\Entity
    {
        $partner = $this->repo->merchant->find($merchantId);

        if ($partner === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Merchant\Entity::ID,
                [
                    Merchant\Entity::ID => $merchantId
                ]);
        }

        // Mark as partner only if the merchant is not a partner
        if ($partner->isPartner() === true)
        {
            return $partner;
        }

        if (empty($partnerType) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER);
        }

        $partner = $this->merchantCore->markAsPartner($partner, $partnerType);

        return $partner;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateBatchPostValidation(array $entries, array $input)
    {
        $totalCount  = count($entries);

        $this->batch->setTotalCount($totalCount);
    }

    /**
     * {@inheritDoc}
     */
    protected function sendProcessedMail()
    {
        return;
    }
}
