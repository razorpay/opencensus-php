<?php

namespace RZP\Models\DeviceDetail;

use RZP\Exception\LogicException;
use Illuminate\Support\Facades\Cookie;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
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

        $this->trace->info(TraceCode::USER_DEVICE_CREATE_DETAIL_REQUEST, [
            "merchant_id" => $merchantId,
            "user_id" => $userId,
            "data" => $input
        ]);

        try
        {
            (new Validator())->validateInput('apps_flyer_id_input', $input);

            $input[Entity::USER_ID] = $userId;
            $input[Entity::MERCHANT_ID] = $merchantId;

            $deviceDetails = $this->repo
                ->user_device_detail
                ->fetchByMerchantIdAndUserId($merchantId, $userId);

            $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip();
            $gclid = $_COOKIE[Constants::G_CLICK_ID] ?? Cookie::get(Constants::G_CLICK_ID);
            $gaClientId = $_COOKIE[Constants::G_CLIENT_ID] ?? Cookie::get(Constants::G_CLIENT_ID);
            if (empty($gaClientId) == false)
            {
                $gaClientId = substr($gaClientId, 6);
            }

            $input[Entity::METADATA][Constants::CLIENT_IP] = $clientIpAddress;
            $input[Entity::METADATA][Constants::G_CLICK_ID] = $gclid;
            $input[Entity::METADATA][Constants::G_CLIENT_ID] = $gaClientId;

            if ($deviceDetails == null)
            {
                $this->trace->info(TraceCode::USER_DEVICE_DETAIL_DOES_NOT_EXIST, [
                        'merchant_id' => $merchantId,
                        'user_id' => $userId
                    ]
                );
                $deviceDetails = $this->createDeviceDetail($input);
            }
            else if ($deviceDetails->getAppsFlyerId() == null or empty($input[Entity::METADATA]) === false)
            {
                $input[Entity::METADATA] = $this->mergeJson($deviceDetails->getMetadata(), $input[Entity::METADATA]);

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
            'merchant_id' => $input[Entity::MERCHANT_ID],
            'user_id' => $input[Entity::USER_ID],
            'input' => $input
        ]);

        $deviceDetail->build($input);

        $this->repo->user_device_detail->saveOrFail($deviceDetail);

        return $deviceDetail;
    }

    protected function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }
        return $existingDetails;
    }

    /**
     * @throws LogicException
     * @throws \Exception
     */
    public function editDeviceDetail(string $id, array $input)
    {
        $deviceDetail = $this->repo->user_device_detail->fetchById($id);

        if (empty($deviceDetail)) {
            throw new \Exception("Device detail not found for ID: $id");
        }

        $deviceDetail->edit($input, 'edit');

        $this->repo->user_device_detail->saveOrFail($deviceDetail);

        return $deviceDetail;
    }

    /**
     * @throws \Throwable
     */
    public function fetchOnboardingWorkflowDataFromPGOS($merchantId): ?array
    {
        if (empty($merchantId)) {
            return null;
        }

        $merchant = $this->repo->merchant->find($merchantId) ?? null;

        $payload = [
            'merchant_id' => $merchantId
        ];

        try {
            $pgosProxyController = new MerchantOnboardingProxyController();
            $startTime = millitime();
            $pgosResponse = $pgosProxyController->handlePGOSProxyRequests(MerchantOnboardingProxyController::GET_MERCHANT_ONBOARDING_DETAILS, $payload, $merchant, true);

            $this->trace->info(TraceCode::PGOS_ONBOARDING_DETAILS_RESPONSE, [
                'merchant_id' => $merchantId,
                'response' => $pgosResponse,
                'duration' => millitime() - $startTime,
            ]);
            if (empty($pgosResponse)) {
                throw new \Exception("PGOS response is empty");
            }

        } catch (\Throwable $e) {

            $this->trace->error(TraceCode::PGOS_ONBOARDING_DETAILS_FETCH_ERROR, [
                'merchant_id' => $merchantId,
                'error'       => $e->getMessage(),
            ]);

            $errorData = json_decode($e->getMessage(), true);
            if (isset($errorData['code']) && $errorData['code'] === 'internal' &&
                isset($errorData['msg']) && $errorData['msg'] === 'db_error: record_not_found') {
                return null;
            }

            throw $e;
        }

        $workflowDetails = $pgosResponse[Constants::WORKFLOW_DETAILS] ?? [];
        $service = $pgosResponse[Constants::WORKFLOW_DETAILS_OWNER_SERVICE] ?? null;

        return [
            Constants::WORKFLOW_DETAILS_OWNER_SERVICE => $service,
            Constants::WORKFLOW_DETAILS => $workflowDetails
        ];
    }


    public function createDeviceDetailForNonPgosMerchants(string $merchantId,$input)
    {
        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantId($merchantId);

        $response = [];

        // Case 1: Check if user_device_detail already exists
        if(!empty($userDeviceDetail)){

            $response["success"] = true;
            $response["message"] = "Device details already exist for this merchant.";

            return $response;
        } else{

            // Case 2: If no device details, check if there is a primary user associated with the merchant
            $merchantUsers = $this->repo->merchant_user->fetchPrimaryUserIdForMerchantIdAndRole($merchantId, 'owner');

            if (!empty($merchantUsers)) {

                $userId = array_first($merchantUsers);

                // Prepare input data for creating device details
                $input = [
                    Entity::MERCHANT_ID => $merchantId,
                    Entity::USER_ID => $userId,
                    Entity::SIGNUP_CAMPAIGN => $input[Entity::SIGNUP_CAMPAIGN] ?? "",
                ];

                try
                {
                    $deviceDetail = $this->createDeviceDetail($input);

                    $response["success"] = true;
                    $response["message"] = "Device details created successfully for this merchant.";
                    $response["device_details"] = $deviceDetail;

                    return $response;
                } catch (\Throwable $e)
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
            }
            else {

                $response["success"] = false;
                $response["message"] = "Merchant user not found for ID: $merchantId";
                return $response;
            }
        }
    }

}
