<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class OfflineDevice extends Base
{
    public function createRegistered(array $attributes = [])
    {
        $defaultAttributes = [
            'status'           => 'created',
            'serial_number'    => 'TestSerial2',
            'manufacturer'     => 'google',
            'model'            => 'pixel',
            'merchant_id'      => null,
            'os'               => null,
            'firmware_version' => null,
            'push_token'       => 'random_push_token',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);
    }

    public function createActivated(array $attributes = [])
    {
        $defaultAttributes = [
            'status'           => 'activated',
            'serial_number'    => 'TestSerial2',
            'manufacturer'     => 'google',
            'model'            => 'pixel',
            'merchant_id'      => 10000000000000,
            'fingerprint'      => 'anc',
            'os'               => 'android',
            'firmware_version' => '4.5.3',
        ];

        return $this->createRegistered($defaultAttributes);
    }
}
