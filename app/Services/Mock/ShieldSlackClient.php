<?php

namespace RZP\Services\Mock;

use RZP\Services\ShieldSlackClient as BaseShieldSlackClient;

class ShieldSlackClient extends BaseShieldSlackClient
{
    public function sendRequest(array $content): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'ok'      => true,
                'channel' => $content['channel'],
                'ts'      => '1626322523.000100',
                'message' => [
                    'bot_id' => 'B123123VCLS',
                    'type'   => 'message',
                    'text'   => $content['text'],
                    'user'   => 'U1231236A11',
                    'ts'     => '1626322523.000100',
                    'team'   => 'T1231236F',
                    'bot_profile' => [
                        'id'      => 'B123123VCLS',
                        'deleted' => false,
                        'name'    => 'risk_alerts',
                        'updated' => 1626273159,
                        'app_id'  => 'A123123A732',
                        'team_id' => 'T1231236F',
                    ],
                ],
            ],
        ];
    }
}
