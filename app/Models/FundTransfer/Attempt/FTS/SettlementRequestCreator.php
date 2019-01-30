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
    const MODE              = 'mode';
    const AMOUNT            = 'amount';
    const CHANNEL           = 'channel';
    const PRODUCT           = 'product';
    const ENTITY_ID         = 'entity_id';
    const NARRATION         = 'narration';
    const SETTLEMENT        = 'settlement';
    const MERCHANT_ID       = 'merchant_id';
    const INITIATE_AT       = 'initiate_at';
    const FUND_ACCOUNT_ID   = 'fund_account_id';

    public function createRequest(array $attempt): array
    {
        $settlementId = $attempt->getSourceId();

        $settlement   = $this->repo->settlement->getSettlementById($settlementId);

        $request['transfer'] = [
            self::MODE              => $attempt->getMode(),
            self::AMOUNT            => $settlement->getAmount(),
            self::CHANNEL           => $attempt->getChannel(),
            self::PRODUCT           => self::SETTLEMENT,
            self::ENTITY_ID         => $attempt->getId(),
            self::NARRATION         => $attempt->getNarration(),
            self::MERCHANT_ID       => $attempt->merchant->getId(),
            self::INITIATE_AT       => $attempt->getInitiateAt(),
            self::FUND_ACCOUNT_ID   => $attempt->bank_account->getFTSAccountId(),
        ];

        return $request;
    }
}