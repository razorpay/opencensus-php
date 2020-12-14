<?php

namespace RZP\Models\PayoutMeta;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(string $partnerMerchantId, string $applicationId, Payout\Entity $payout)
    {
        $payoutId = $payout->getId();

        $input = [
            Entity::PAYOUT_ID      => $payoutId,
            Entity::PARTNER_ID     => $partnerMerchantId,
            Entity::APPLICATION_ID => $applicationId,
        ];

        $this->trace->info(
            TraceCode::PAYOUT_META_ENTITY_CREATE_REQUEST,
            $input
        );

        $this->validateIfPayoutAlreadyExistsForPartner($payoutId);

        $payoutMeta = (new Entity)->build($input);

        $this->repo->saveOrFail($payoutMeta);

        $this->trace->info(
            TraceCode::PAYOUT_META_ENTITY_CREATED,
            $payoutMeta->toArrayInternal()
        );
    }

    protected function validateIfPayoutAlreadyExistsForPartner(string $payoutId)
    {
        /** @var Entity $payoutMeta */
        $payoutMeta = $this->repo->payouts_meta->getPayoutMetaByPayoutIdPartnerId($payoutId);

        if ($payoutMeta !== null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_META_ALREADY_EXISTS,
                null,
                [
                    'payout_meta'     => $payoutMeta->toArrayInternal(),
                    Entity::PAYOUT_ID => $payoutId,
                ]
            );
        }
    }
}
