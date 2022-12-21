<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Adjustment;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Entity as E;

class SettlementReconReport extends BasicEntityReport
{
    protected $batchLimit = 1000;

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
            E::SETTLEMENT,
            E::CREDIT_REPAYMENT
        ]
    ];

    protected $allowed = [
        E::TRANSACTION
    ];

    /**
     * Gets report data as array
     *
     * Not being used anywhere on dashboard
     * Keeping it to maintain backward compatibility
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return $data array
     */
    public function getReport(array $input)
    {
        $this->validateInput($input);

        $this->setDefaults();

        list($from, $to, $count, $skip) = $this->getParamsForReport($input);

        list($data, $count) = $this->getReportData($from, $to, $count, $skip);

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

    /**
     * Returns formatted data to be shown in report
     *
     * @param $entities array
     * @return array
     */
    protected function fetchFormattedDataForReport($entities): array
    {
        return $entities->toArrayPublic();
    }

}
