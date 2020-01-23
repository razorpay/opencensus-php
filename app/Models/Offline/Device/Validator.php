<?php

namespace RZP\Models\Offline\Device;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $registerRules = [
        Entity::SERIAL_NUMBER   => 'required|string|max:256',
        Entity::TYPE            => 'required|in:android',
        Entity::MANUFACTURER    => 'required',
        Entity::MODEL           => 'required',
    ];

    protected static $linkRules = [
        Entity::ACTIVATION_TOKEN => 'required|string|max:256'
    ];

    protected static $initiateActivationRules = [
        Entity::SERIAL_NUMBER    => 'required|string|max:256',
        Entity::OS               => 'required|in:android,custom',
        Entity::FIRMWARE_VERSION => 'required',
        Entity::FINGERPRINT      => 'required|string|max:256',
        Entity::PUSH_TOKEN       => 'required|string',
    ];

    protected $registerValidators = [
        'device_uniqueness',
    ];

    public function validateDeviceUniqueness($input)
    {
        $type         = $input['type'];
        $serialNumber = mb_strtolower($input['serial_number']);
        $manufacturer = mb_strtolower($input['manufacturer']);

        $devicesCount = app('repo')->offline_device
                              ->countBySerialNumberManufacturerAndType($serialNumber, $manufacturer, $type);

        if ($devicesCount > 0)
        {
            // throw exception if merchant by that email already exists
            throw new Exception\BadRequestValidationFailureException(
                'A device with similiar info already exists');
        }
    }
}
