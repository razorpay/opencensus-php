<?php

namespace RZP\Models\DeviceDetail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    public function createUserDeviceDetail(array $input)
    {
        $user = $this->app['basicauth']->getUser();
        $userId = $user['id'];
        $merchantId = $this->merchant->getId();

        $this->trace->info(TraceCode::USER_DEVICE_CREATE_DETAIL_REQUEST,[
            "merchant_id" => $merchantId,
            "user_id"     => $userId,
            "data"        => $input
        ]);

        try
        {
            (new Validator())->validateInput('apps_flyer_id_input', $input);

            $input[Entity::USER_ID] = $userId;
            $input[Entity::MERCHANT_ID] = $merchantId;

            $deviceDetails = $this->repo
                ->user_device_detail
                ->fetchByMerchantIdAndUserId($merchantId, $userId);

            if ($deviceDetails == null)
            {
                $this->trace->info(TraceCode::USER_DEVICE_DETAIL_DOES_NOT_EXIST, [
                        'merchant_id' => $merchantId,
                        'user_id' => $userId
                    ]
                );
                $deviceDetails = $this->createDeviceDetail($input);
            }
            else if($deviceDetails->getAppsFlyerId() == null)
            {
                $deviceDetails->edit($input, 'edit');

                $this->repo->user_device_detail->saveOrFail($deviceDetails);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            $this->trace->info(
                TraceCode::USER_DEVICE_DETAIL_SAVE_FAILED,
                ['input' => $input]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR
            );
        }
        return [
            Entity::APPSFLYER_ID => $deviceDetails->getAppsFlyerId()
        ];
    }

    public function createDeviceDetail(array $input)
    {
        $deviceDetail = new Entity;

        $deviceDetail->generateId();

        $this->trace->info(TraceCode::USER_DEVICE_CREATE_DETAIL, [
            'merchant_id'  => $input[Entity::MERCHANT_ID],
            'user_id'      => $input[Entity::USER_ID],
            'input'        => $input
        ]);

        $deviceDetail->build($input);

        $this->repo->user_device_detail->saveOrFail($deviceDetail);

        return $deviceDetail;
    }
}
