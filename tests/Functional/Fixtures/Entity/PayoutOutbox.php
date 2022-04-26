<?php
namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class PayoutOutbox extends Base
{
    const MERCHANT_ID    = '10000000000000';
    const USER_ID    = 'MerchantUser01';

    public function createOrphanedPayout(array $attributes, $mode = 'test')
    {
        $request_type = $attributes['request_type'] ?? 'payouts';

        $source = $attributes['source'] ?? 'dashboard';

        $product = $attributes['product'] ?? 'primary';

        DB::connection($mode)->table('payout_outbox')
            ->insert([
                'id' => '123',
                'merchant_id' => self::MERCHANT_ID,
                'user_id'     => self::USER_ID,
                'payout_data' => '{"mode": "amazonpay", "notes": [], "amount": 100, "origin": "dashboard", "purpose": "testing 102", "currency": "INR", "narration": "Aman Fund Transfer", "balance_id": "H1tcrSbxUb7TJi", "fund_account_id": "fa_IzpdqLUJS2Kzdt", "queue_if_low_balance": 1}',
                'product'     => $product,
                'source'     => $source,
                'request_type'     => $request_type,
                'created_at'  => Carbon::now()->getTimestamp(),
                'expires_at'  => Carbon::now()->getTimestamp(),
            ]);
    }
}
