<?php

namespace Gateway\Billdesk;

use EE\Exception;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Billdesk';

    public function findByPaymentIdAndAction($paymentId, $action)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $paymentId)
                    ->where('action', '=', $action)
                    ->firstOrFail();
    }
}