<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Entity as E;

class SettlementReconReport extends BasicEntityReport
{
    // Maps the transaction source to the entities to be fetched for it
    protected $entityToRelationFetchMap = [
        E::TRANSACTION => [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT  => [E::ORDER, E::CARD],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::CARD,
                E::PAYMENT . '.' . E::ORDER,
            ],
            E::ADJUSTMENT   => [
                Adjustment\Entity::ENTITY,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::CARD,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::ORDER,
            ],
        ]
    ];

    protected $allowed = [
        E::TRANSACTION
    ];

    protected function fetchFormattedDataForReport($entities): array
    {
        $data = [];

        foreach ($entities as $order)
        {
            switch ($order->getStatus())
            {
                case Order\Status::CREATED:
                    $row = $this->createFailureEntry($order, 'pending', 'Razorpay Payment does not exists');
                    break;

                case Order\Status::ATTEMPTED:
                    $row = $this->createEntryForAttemptedOrder($order);
                    break;

                case Order\Status::PAID:
                    $row = $this->createEntryForPaidOrder($order);
                    break;
            }

            $data[] = $row;
        }

        return $data;
    }

    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        $entity = $this->entity;

        $repo = $this->repo->$entity;

        return $repo->fetchEntitiesForReconReport(
                        $merchantId,
                        $from,
                        $to,
                        $count,
                        $skip,
                        $this->relationsToFetch
        );
    }

}
