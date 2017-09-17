<?php

namespace RZP\Models\Feature;

use Config;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsRepository;

class Core extends Base\Core
{
    public function create($input, bool $shouldSync = false)
    {
        $feature = (new Entity)->build($input);

        $feature = $feature->generateId();

        $existingFeatures = $this->repo->feature->findByEntityId($feature->getEntityId());

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        $this->trace->info(TraceCode::MERCHANT_FEATURE_EDIT_REQUEST,
            [
                'merchant_id'  => $feature->getEntityId(),
                'old_features' => $assignedFeatureNames,
                'new_feature'  => $feature->getName()
            ]);

        $this->traceFeatureSyncing($feature, $shouldSync);

        if ($shouldSync === true)
        {
            $this->saveAndSyncOrFail($feature);
        }
        else
        {
            $saved = $this->saveFeatureOrFail($feature, $assignedFeatureNames);

            if ($saved === false)
            {
                return null;
            }
        }

        $this->notifyOnSlack($feature);

        return $feature;
    }

    public function delete($entityId, $feature, bool $shouldSync = false)
    {
        $this->trace->info(TraceCode::FEATURE_DELETE_REQUEST, $feature->toArrayPublic());

        // Workflow
        list($original, $dirty) = [
            ['feature' => $feature->getName()],
            ['feature' => null],
        ];

        $this->app['workflow']
             ->setEntity($feature->getEntity())
             ->handle($original, $dirty);

        $this->traceFeatureSyncing($feature, $shouldSync);

        if ($shouldSync === true)
        {
            $this->deleteAndSyncOrFail($feature);
        }
        else
        {
            $this->repo->feature->delete($feature);
        }

        (new Core)->notifyOnSlack($feature, true);

        // We create tag also along with feature.
        (new Merchant\Service)->deleteTag($entityId, $feature->getName());
    }

    public function notifyOnSlack($feature, $featureDeleted = false)
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

        $data = [];

        $this->app['slack']->queue(
            $message,
            $data,
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }

    /**
     * Save feature with sync: Adds features to test and live
     * DB's if they don't already exist
     *
     * @param Entity $entity
     */
    protected function saveAndSyncOrFail(Entity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $featureName = $entity->getName();
            $entityId    = $entity->getEntityId();

            $testEntity = $this->repo->feature->findByEntityIdAndName($entityId, $featureName, Mode::TEST);
            $liveEntity = $this->repo->feature->findByEntityIdAndName($entityId, $featureName, Mode::LIVE);

            if ($testEntity === null)
            {
                $testEntity = clone $entity;
                $testEntity->setConnection(Mode::TEST);
                $testEntity->saveOrFail();
            }

            if ($liveEntity === null)
            {
                $liveEntity = clone $entity;
                $liveEntity->setConnection(Mode::LIVE);
                $liveEntity->saveOrFail();
            }
        });
    }

    protected function saveFeatureOrFail(Entity $feature, array $assignedFeatureNames): bool
    {
        $isFeatureAssigned = in_array($feature->getName(), $assignedFeatureNames, true);

        //
        // If the feature is already assigned, there's nothing
        // to save
        //
        if ($isFeatureAssigned === true)
        {
            return false;
        }

        $this->repo->feature->saveOrFail($feature);

        return true;
    }

    /**
     * Delete a feature with sync: removes the record
     * from both test/live DB's if present
     *
     * @param Entity $entity
     */
    protected function deleteAndSyncOrFail(Entity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $featureName = $entity->getName();
            $entityId    = $entity->getEntityId();

            $testEntity = $this->repo->feature->findByEntityIdAndName($entityId, $featureName, Mode::TEST);
            $liveEntity = $this->repo->feature->findByEntityIdAndName($entityId, $featureName, Mode::LIVE);

            if ($testEntity !== null)
            {
                $testEntity->deleteOrFail();
                $this->repo->feature->syncToEs($entity, EsRepository::DELETE, null, Mode::TEST);
            }

            if ($liveEntity !== null)
            {
                $liveEntity->deleteOrFail();
                $this->repo->feature->syncToEs($entity, EsRepository::DELETE, null, Mode::LIVE);
            }
        });
    }

    protected function traceFeatureSyncing(Entity $feature, bool $shouldSync)
    {
        $traceCode = ($shouldSync === true) ? TraceCode::FEATURE_SYNCING : TraceCode::FEATURE_NOT_SYNCING;

        $traceData = [
            'entity_id'     => $feature->getEntityId(),
            'feature_name'  => $feature->getName()
        ];

        $this->trace->info($traceCode, $traceData);
    }
}
