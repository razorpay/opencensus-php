<?php

namespace Gateway\Base;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    public function findByPaymentIdAndActionOrFail($paymentId, $action)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $paymentId)
                    ->where('action', '=', $action)
                    ->firstOrFail();
    }

    public function findByPaymentIdAndAction($paymentId, $action)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $paymentId)
                    ->where('action', '=', $action)
                    ->first();
    }

    public function findRefunds($paymentId)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $paymentId)
                    ->where('action', '=', 'refund')
                    ->get();
    }
}