<?php
/**
 * Created by PhpStorm.
 * User: amogh
 * Date: 2019-01-29
 * Time: 23:32
 */

namespace RZP\Models\FundTransfer\Attempt\FTS;

use RZP\Models\Base;

class SettlementRequestCreator extends Base\Core
{
    const MERCHANT_ID       = 'merchant_id';
    const PRODUCT           = 'product';
    const ENTITY_ID         = 'entity_id';
    const FUND_ACCOUNT_ID   = 'fund_account_id';
    const CHANNEL           = 'channel';
    const AMOUNT            = 'amount';
    const MODE              = 'mode';
    const INITIATE_AT       = 'initiate_at';
    const SETTLEMENT        = 'settlement';
    const NARRATION         = 'narration';

    public function createRequest(array $attempt): array
    {
        $settlementId = $attempt->getSourceId();

        $settlement   = $this->repo->settlement->getSettlementById($settlementId);

        $request['transfer'] = [
            self::PRODUCT           => self::SETTLEMENT,
            self::MERCHANT_ID       => $attempt->merchant->getId(),
            self::ENTITY_ID         => $attempt->getId(),
            self::FUND_ACCOUNT_ID   => $attempt->bank_account->getFTSAccountId(),
            self::CHANNEL           => $attempt->getChannel(),
            self::AMOUNT            => $settlement->getAmount(),
            self::MODE              => $attempt->getMode(),
            self::INITIATE_AT       => $attempt->getInitiateAt(),
            self::NARRATION         => $attempt->getNarration(),
        ];

        return $request;
    }
}