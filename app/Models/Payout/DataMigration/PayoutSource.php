<?php

namespace RZP\Models\Payout\DataMigration;

use App;
use Illuminate\Foundation\Application;

use RZP\Models\Payout\Entity;
use RZP\Base\RepositoryManager;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;

class PayoutSource
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

    public function getPayoutServicePayoutSourcesForApiPayout(Entity $payout)
    {
        $payoutServicePayoutSource = [];

        $payoutSources = $this->repo->payout_source->getPayoutSourcesByPayoutId($payout->getId());

        if (empty($payoutSources) === false)
        {
            foreach ($payoutSources as $payoutSource)
            {
                $payoutServicePayoutSource[] = $this->createPayoutServicePayoutSource($payoutSource);
            }
        }

        return $payoutServicePayoutSource;
    }

    protected function createPayoutServicePayoutSource(PayoutSourceEntity $payoutSource)
    {
        return [
            PayoutSourceEntity::ID          => $payoutSource->getId(),
            PayoutSourceEntity::PAYOUT_ID   => $payoutSource->getPayoutId(),
            PayoutSourceEntity::SOURCE_ID   => $payoutSource->getSourceId(),
            PayoutSourceEntity::SOURCE_TYPE => $payoutSource->getSourceType(),
            PayoutSourceEntity::PRIORITY    => $payoutSource->getPriority(),
            PayoutSourceEntity::CREATED_AT  => $payoutSource->getCreatedAt(),
            PayoutSourceEntity::UPDATED_AT  => $payoutSource->getUpdatedAt(),
        ];
    }
}
