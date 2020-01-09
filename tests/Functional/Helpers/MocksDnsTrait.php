<?php

namespace RZP\Tests\Functional\Helpers;

use Symfony\Bridge\PhpUnit\DnsMock;

/**
 * To use this, annotate the class with
 * dns-sensitive (see WebhookTest.php)
 * and call setupMockDns before any tests
 * that use DNS
 */
trait MocksDnsTrait
{
    private function setupMockDns()
    {
        DnsMock::withMockedHosts([
            'webhook.com' => [
                [
                    'type'  => 'A',
                    'ip'    => '182.74.201.50',
                ]
            ],
            'example.com' => [
                [
                    'type' => 'A',
                    'ip' => '1.2.3.4',
                ],
            ],
            'www.example.com' => [
                [
                    'type' => 'A',
                    'ip' => '1.2.3.4',
                ],
            ],
            'abc.com' => [
                [
                    'type' => 'A',
                    'ip' => '1.2.3.4',
                ],
            ],
            '10.0.0.1.xip.io' => [
                [
                    'type'  => 'A',
                    'ip'    => '10.0.0.1',
                ],
            ],
            '169.254.169.254.xip.io'    => [
                [
                    'type'  => 'A',
                    'ip'    => '169.254.169.254',
                ]
            ],
            'EXAMPLE.CoM' => [
                [
                    'type' => 'A',
                    'ip'   => '10.0.0.1',
                ],
            ],
        ]);
    }
}
