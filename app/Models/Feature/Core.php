<?php

namespace RZP\Models\Feature;

use Config;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;

class Core extends Base\Core
{
    /**
     * Create feature
     *
     * @param array $input
     * @param bool  $shouldSync Should the entity be save on both test and live
     *
     * @return Entity
     */
    public function create(array $input, bool $shouldSync = false): Entity
    {
        $feature = (new Entity)->build($input);

        $feature->generateId();

        $existingFeatures = $this->repo->feature->findByEntityId($feature->getEntityId());

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        $this->trace->info(TraceCode::MERCHANT_FEATURE_EDIT_REQUEST,
                           [
                               PublicEntity::MERCHANT_ID => $feature->getEntityId(),
                               Entity::OLD_FEATURES      => $assignedFeatureNames,
                               Entity::NEW_FEATURE       => $feature->getName(),
                               Entity::SHOULD_SYNC       => $shouldSync
                           ]);

        $this->repo->feature->saveAndSyncIfApplicableOrFail(
            $feature,
            $assignedFeatureNames,
            $shouldSync);

        $this->notifyOnSlack($feature);

        return $feature;
    }

    /**
     * Delete feature
     *
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    public function delete(Entity $feature, bool $shouldSync = false)
    {
        $this->trace->info(
            TraceCode::FEATURE_DELETE_REQUEST,
            [
                Entity::FEATURE     => $feature->toArrayPublic(),
                Entity::SHOULD_SYNC => $shouldSync
            ]);

        // Workflow
        list($original, $dirty) = [
            ['feature' => $feature->getName()],
            ['feature' => null],
        ];

        $this->app['workflow']
             ->setEntity($feature->getEntity())
             ->handle($original, $dirty);

        $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);

        $this->notifyOnSlack($feature, true);
    }

    /**
     * Send a slack notification on feature create/delete
     *
     * @param Entity $feature
     * @param bool   $featureDeleted
     */
    protected function notifyOnSlack(Entity $feature, bool $featureDeleted = false)
    {
        $message = $feature->getDashboardEntityLinkForSlack($feature->getName());

        if ($featureDeleted === true)
        {
            $message .= ' deleted from ';
        }
        else
        {
            $message .= ' added to ';
        }

        $user = $this->getInternalUsernameOrEmail();

        $message .= $feature->getEntityId() . ' by ' . $user;

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }
}
