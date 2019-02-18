<?php
namespace RZP\Models\Gateway\Settlement\Processor;

use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Base\CardCacheTrait;

class Paysecure extends Base
{
    use CardCacheTrait;

    protected $secureCacheDriver;

    const CACHE_KEY = 'paysecure_%s_card_details';
    const CACHE_TTL = 180;

    public function getPayments(array $input): PublicCollection
    {
        return $this->repo->payment->fetchPaysecureAuthorizedAndCapturedPaymentsBetweenTimestampsToSettle(
            $input[self::FROM],
            $input[self::TO]
        );
    }

    protected function preProcessGatewayInput(&$input)
    {
        return;
    }
}
