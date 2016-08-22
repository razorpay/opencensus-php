<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'payment_analytics';

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - 30 * 60;

        return $this->newQuery()
                    ->where(Entity::CHECKOUT_ID, '=', $checkoutId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    public function findForPayment($payment_id, $id = null)
    {
        $repo = $this->repo;

        $results =  $repo->where(Entity::PAYMENT_ID, '=', $paymentId);

        if ($id !== null)
        {
            $results = $results->where(Entity::TERMINAL_ID, '=', $id);
        }

        return $results->get();
    }

    public function findForTerminal($id)
    {
        $repo = $this->repo;

        return $repo->where(Entity::TERMINAL_ID, '=', $id)
                    ->get();
    }

    public function findBetweenTimestampsForTerminal($from, $to, $terminal_id, $payment_id = null)
    {
        $repo = $this->repo->payment_analytics;

        $results = $repo::withTrashed()
                        ->where(Entity::TERMINAL_ID, '=', $terminal_id)
                        ->where(Entity::TIMESTAMP, '>=', $from)
                        ->where(Entity::TIMESTAMP, '<=', $to);

        if($payment_id !== null)
        {
            $results = $results->where(Entity::PAYMENT_ID, '=', $payment_id);
        }

        return $results->get();
    }
}