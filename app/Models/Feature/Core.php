<?php

namespace RZP\Models\Feature;

use Config;
use RZP\Constants\Mode;
use RZP\Models\Base;
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

        if ($shouldSync === true)
        {
            $this->trace->info(TraceCode::FEATURE_SYNCED,
                [
                    'entity_id'     => $feature->getEntityId(),
                    'entity_name'   => $feature->getName()
                ]);

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

    public function saveFeatureOrFail($feature, $assignedFeatureNames)
    {
        if (in_array($feature->getName(), $assignedFeatureNames, true) === false)
        {
            $this->trace->info(TraceCode::FEATURE_NOT_SYNCED,
                [
                    'entity_id'     => $feature->getEntityId(),
                    'entity_name'   => $feature->getName()
                ]);

            $this->repo->feature->saveOrFail($feature);

            return true;
        }
        else
        {
            return false;
        }
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

        if ($shouldSync === true)
        {
            $this->trace->info(TraceCode::FEATURE_SYNCED,
                [
                    'entity_id'     => $feature->getEntityId(),
                    'entity_name'   => $feature->getName()
                ]);
            $this->deleteAndSyncOrFail($feature);
        }
        else
        {
            $this->trace->info(TraceCode::FEATURE_NOT_SYNCED,
                [
                    'entity_id'     => $feature->getEntityId(),
                    'entity_name'   => $feature->getName()
                ]);
            $this->repo->feature->delete($feature);
        }

        (new Core)->notifyOnSlack($feature, true);

        //we create tag also along with feature.
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

    public function saveAndSyncOrFail($entity): bool
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $entityName = $entity->getName();

            $entityId = $entity->getEntityId();

            $testEntity = $this->repo->feature->findByEntityIdAndName($entityId, $entityName, Mode::TEST);

            $liveEntity = $this->repo->feature->findByEntityIdAndName($entityId, $entityName, Mode::LIVE);

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

            return true;
        });

        return false;
    }

    public function deleteAndSyncOrFail($entity): bool
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $entityName = $entity->getName();

            $entityId = $entity->getEntityId();

            $testEntity = $this->repo->feature->findByEntityIdAndName($entityId, $entityName, Mode::TEST);

            $liveEntity = $this->repo->feature->findByEntityIdAndName($entityId, $entityName, Mode::LIVE);

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

            return true;
        });

        return false;
    }
}
