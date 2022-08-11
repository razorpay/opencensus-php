<?php

namespace RZP\Models\Payout\DataMigration;

use App;
use Illuminate\Foundation\Application;

use RZP\Models\Payout\Entity;
use RZP\Base\RepositoryManager;
use RZP\Models\PayoutsStatusDetails;

class PayoutStatusDetails
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function getPayoutServicePayoutStatusDetailsForApiPayout(Entity $payout)
    {
        $payoutStatusDetails = $this->repo->payouts_status_details->fetchPayoutStatusDetailsByPayoutId($payout->getId());

        $payoutStatusDetailsForPS = [];

        if (empty($payoutStatusDetails) === true)
        {
            return $payoutStatusDetailsForPS;
        }

        foreach ($payoutStatusDetails as $payoutStatusDetail)
        {
            $payoutStatusDetailsForPS[] = $this->createPayoutServicePayoutStatusDetails($payoutStatusDetail);
        }

        return $payoutStatusDetailsForPS;
    }

    protected function createPayoutServicePayoutStatusDetails(PayoutsStatusDetails\Entity $payoutStatusDetails)
    {
        return [
            PayoutsStatusDetails\Entity::ID           => $payoutStatusDetails->getId(),
            PayoutsStatusDetails\Entity::PAYOUT_ID    => $payoutStatusDetails->getPayoutId(),
            PayoutsStatusDetails\Entity::CREATED_AT   => $payoutStatusDetails->getCreatedAt(),
            PayoutsStatusDetails\Entity::UPDATED_AT   => $payoutStatusDetails->getUpdatedAt(),
            PayoutsStatusDetails\Entity::DESCRIPTION  => $payoutStatusDetails->getDescription(),
            PayoutsStatusDetails\Entity::MODE         => $payoutStatusDetails->getMode(),
            PayoutsStatusDetails\Entity::REASON       => $payoutStatusDetails->getReason(),
            PayoutsStatusDetails\Entity::STATUS       => $payoutStatusDetails->getStatus(),
            PayoutsStatusDetails\Entity::TRIGGERED_BY => $payoutStatusDetails->getTriggeredBy(),
        ];
    }
}
