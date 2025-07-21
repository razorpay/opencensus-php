<?php

namespace RZP\Models\User;

use RZP\Models\Base\UniqueIdEntity as UniqueIdEntity;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class SplitzExperimentEvaluator extends Base\Core
{
    public function isLoginViaSsoEnabled($email): bool
    {
        $experimentId = "app.user_login_via_sso_experiment_id";
        $properties = [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get($experimentId),
            'request_data'  => json_encode(['email' => $email, 'mode' => $this->mode]),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        return  $variant == "enable";
    }
}
