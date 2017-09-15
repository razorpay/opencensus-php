<?php

namespace RZP\Models\Feature;

use Config;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create($input, array $options = array())
    {
        $feature = (new Entity)->build($input);

        $feature = $feature->generateId();

        $existingFeatures = $this->repo->feature->findByEntityId($feature->getEntityId());

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        if (in_array($feature->getName(), $assignedFeatureNames, true) === false)
        {
            $this->trace->info(TraceCode::MERCHANT_FEATURE_EDIT,
                array('merchant_id'  => $feature->getEntityId(),
                      'old_features' => $assignedFeatureNames,
                      'new_feature'  => $feature->getName()));

            $this->repo->saveOrFail($feature, $options);

            $this->notifyOnSlack($feature);

            return $feature;
        }

        return null;
    }

    public function delete($entityId, $feature)
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

        $this->repo->feature->delete($feature);

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
}
