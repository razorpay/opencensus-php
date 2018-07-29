<?php

namespace RZP\Models\Batch\Processor;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Error\PublicErrorDescription;

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

        $partner = $this->repo->merchant->findOrFail($partnerId);

        // Mark as partner only if the merchant is not a partner
        if ($partner->isPartner() === false)
        {
            if (empty($partnerType) === true)
            {
                $entry[Header::STATUS]            = Status::FAILURE;
                $entry[Header::ERROR_CODE]        = ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER;
                $entry[Header::ERROR_DESCRIPTION] = PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER;

                return;
            }

            $partner = $this->merchantCore->markAsPartner($partner, $partnerType);
        }

        if (empty($submerchantId) === false)
        {
            $submerchant = $this->repo->merchant->findOrFail($submerchantId);


            $this->merchantCore->createPartnerSubmerchantAccessMap($partner, $submerchant);
        }

        $entry[Header::STATUS] = Status::SUCCESS;
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
