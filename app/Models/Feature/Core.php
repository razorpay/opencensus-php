<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use Config;

class Core extends Base\Core
{
    public function create($input)
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

            $this->repo->saveOrFail($feature);

            $this->notifyOnSlack($feature);

            return $feature;
        }

        return null;
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

        $message.= $feature->getEntityId() . ' by ' . $user;

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
