<?php

namespace RZP\Models\Offline\Device;

use FCM;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use RZP\Base\ConnectionType;

class Service extends Base\Service
{
    public function fetch($input)
    {
        $merchantId = $this->merchant->getId();

        $devices = $this->repo->offline_device->fetch($input, $merchantId, ConnectionType::SLAVE);

        return $devices->toArrayPublic();
    }

    public function register($input)
    {
        $device = (new Entity)->register($input);

        $this->repo->saveOrFail($device);

        return $device->toArrayPublic();
    }

    public function link($input)
    {
        (new Validator)->validateInput('link', $input);

        $device = $this->repo->offline_device->findByActivationToken($input['activation_token']);

        if ($device === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please enter a valid activation code');
        }

        $device->merchant()->associate($this->merchant);

        $device->setActivated('activated');

        // todo: Remove activation token

        $this->repo->saveOrFail($device);

        return $device->toArrayPublic();
    }

    public function initiateActivation($input)
    {
        (new Validator)->validateInput('initiate_activation', $input);

        $device = $this->repo->offline_device->findBySerialNumber($input['serial_number']);

        if ($device === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Incorrect device data provided.');
        }

        $device->edit($input, 'initiate_activation');

        $this->repo->saveOrFail($device);

        return $device->toArrayPublic();
    }

    public function fetchVaOrderStatus($deviceId, $virtualAccountId)
    {
        $device = $this->repo->offline_device->findByPublicId($deviceId);

        $va = $this->repo->virtual_account->findByPublicIdAndMerchant($virtualAccountId, $device->merchant);

        if ($va->hasOrder() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Virtual account provided');
        }

        return ['status' => $va->entity->getStatus()];
    }

    public function push($device, $payload)
    {
        $notificationBuilder = new PayloadNotificationBuilder('New QR');

        $notificationBuilder->setBody('create_qr');

        $dataBuilder = new PayloadDataBuilder();

        $dataBuilder->addData($payload);

        $notification = $notificationBuilder->build();

        $data = $dataBuilder->build();

        try
        {
            FCM::sendTo($device['push_token'], null, $notification, $data);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }
    }
}
