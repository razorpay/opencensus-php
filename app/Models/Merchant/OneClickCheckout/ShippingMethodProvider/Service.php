<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider;

use App;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Models\Merchant\Metric;
use RZP\Models\Pincode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Trace\TraceCode;


class Service extends Base\Service
{

    const SHIPPING_SERVICE_SERVICEABILITY_PATH = "twirp/rzp.shipping.serviceability.v1.ServiceabilityAPI/Check";

    public function list($shippingProviderId)
    {
        if (empty($shippingProviderId) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "missing shipping_provider_id"
            );
        }

        $items = [];
        $shippingMethodProviderEntity = $this->merchant->getShippingMethodProvider();
        if ($shippingMethodProviderEntity !== null)
        {
            $shippingMethodProvider = $shippingMethodProviderEntity->getValueJson();
            if ($shippingMethodProvider[Constants::SHIPPING_PROVIDER_ID] === $shippingProviderId)
            {
                array_push($items, $shippingMethodProvider);
            }
        }

        return [
            'entity' => 'collection',
            'count'  => count($items),
            'items'  => $items,
        ];

    }

    public function create($input)
    {
        (new Validator)->validateInput('shippingProvider', $input);
        $id = UniqueIdEntity::generateUniqueId();
        return $this->createOrUpdate($input, $id);
    }

    public function update($input, $id)
    {
        (new Validator)->validateInput('shippingProvider', $input);
        $shippingMethodProviderEntity = $this->getAndValidateById($id);
        $shippingMethodProvider = $shippingMethodProviderEntity->getValueJson();
        if ($shippingMethodProvider[Constants::SHIPPING_PROVIDER_ID]
            !== $input[Constants::SHIPPING_PROVIDER_ID])
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "invalid shipping_provider_id"
            );
        }

        return $this->createOrUpdate($input, $id);

    }

    public function delete($id)
    {
        $shippingMethodProviderEntity = $this->getAndValidateById($id);
        $shippingMethodProviderEntity->delete();
    }

    protected function createOrUpdate($input, $id)
    {

        $input[Constants::ID] = $id;

        $pincode = $input[Constants::WAREHOUSE_PINCODE] ?? null;
        $pincodeValidator = new Pincode\Validator(Pincode\Pincode::IN);
        if ($pincode !== null and $pincodeValidator->validate($pincode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $pincode . ' is not valid.');
        }

        $shippingFee = $input[Constants::SHIPPING_FEE_RULE] ?? null;
        $shippingFeeRule = null;
        if ($shippingFee !== null)
        {
            $shippingFeeRule = new FeeRule($shippingFee);
            $shippingFeeRule->validate();
        }


        $codFeeRule = null;
        if (isset($input[Constants::ENABLE_COD]) and $input[Constants::ENABLE_COD] === true)
        {
            $codFeeRule = $input[Constants::COD_FEE_RULE];
            $codFeeRule = new FeeRule($codFeeRule);
            $codFeeRule->validate();
        } else {
            $input[Constants::ENABLE_COD] = false;
        }

        $shippingMethodProviderEntity =
            $this->app['repo']->transaction(function () use ($input, $codFeeRule, $shippingFeeRule)
        {

            $merchantService = new MerchantService;

            $shippingMethodProviderEntity = $merchantService->updateShippingMethodProviderConfig($input);

            if ($codFeeRule !== null && $codFeeRule->isSlabRuleType())
            {
                $codSlabs = $codFeeRule->adaptToSlabsTableEntity();
                $this->app['trace']->info(TraceCode::MERCHANT_COD_SLABS, $codSlabs);
                $merchantService->updateCodSlabs($codSlabs);
            }

            if ($shippingFeeRule !== null and $shippingFeeRule->isSlabRuleType())
            {
                $shippingSlabs = $shippingFeeRule->adaptToSlabsTableEntity();
                $this->app['trace']->info(TraceCode::MERCHANT_SHIPPING_SLABS, $shippingSlabs);
                $merchantService->updateShippingSlabs($shippingSlabs);
            }

            return $shippingMethodProviderEntity;

        });
        return $shippingMethodProviderEntity->getValueJson();
    }

    protected function getAndValidateById($id)
    {
        if (empty($id) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "missing id"
            );
        }

        $shippingMethodProviderEntity = $this->merchant->getShippingMethodProvider();
        if ($shippingMethodProviderEntity === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "invalid id, no entity exists"
            );
        }

        $shippingMethodProvider = $shippingMethodProviderEntity->getValueJson();

        if ($shippingMethodProvider[Constants::ID] !== $id)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "invalid id"
            );
        }
        return $shippingMethodProviderEntity;
    }

    public function getShippingInfoForAddress(array $shippingMethodProvider, array $input, string $merchantId): array
    {
        $result = [];
        $serviceability = $this->getServiceability($shippingMethodProvider, $input, $merchantId);

        $result['serviceable'] = $serviceability['serviceable'];
        $result['cod'] = $serviceability['cod'];

        $shippingFee = $shippingMethodProvider[Constants::SHIPPING_FEE_RULE];
        $shippingFeeRule = new FeeRule($shippingFee);
        if ($shippingFeeRule->isSlabRuleType() === false) {
            $result['shipping_fee'] = $shippingFeeRule->getFee();
        }

        if (isset($shippingMethodProvider[Constants::ENABLE_COD]) and $shippingMethodProvider[Constants::ENABLE_COD] === true && $result['cod'] === true) {
            $codFee = $shippingMethodProvider[Constants::COD_FEE_RULE];
            $codFeeRule = new FeeRule($codFee);
            if ($codFeeRule->isSlabRuleType() === false) {
                $result['cod_fee'] = $codFeeRule->getFee();
            }
        } else {
            $result['cod'] = false;
            $result['cod_fee'] = 0;
        }

        return $result;
    }

    public function getServiceability(array $shippingMethodProvider, array $input, string $merchantId): array
    {
        $address = $input['address'];
        $serviceabilityRequest = [
            "merchant_id" => $merchantId,
            "shipping_provider_id" => $shippingMethodProvider[Constants::SHIPPING_PROVIDER_ID],
            "pickup_location" => [
                "pin_code" => $shippingMethodProvider[Constants::WAREHOUSE_PINCODE],
                "country_code" => "IN"
            ],
            "delivery_location" => [
                "pin_code" => $address["zipcode"],
                "country_code" => $address["country"],
            ],
            "order" => [
                "id" => $input['order_id'],
                "weight" => 1,
            ]
        ];

        $result = [
            "serviceable" => false,
            "cod" => false
        ];

        try
        {
            $res = $this->app['shipping_service_client']->sendRequest(
                self::SHIPPING_SERVICE_SERVICEABILITY_PATH,
                $serviceabilityRequest, Requests::POST);

            if (isset($res["serviceability"]) === true) {
                $serviceability = $res["serviceability"];
                if (isset($serviceability["prepaid"]) === true && $serviceability["prepaid"] === true) {
                    $result["serviceable"] = true;
                }
                if (isset($serviceability["cod"]) === true && $serviceability["cod"] === true) {
                    $result["cod"] = true;
                }
            }
            return $result;
        }
        catch (BadRequestException $exception)
        {
            $data = [
                'exception' => $exception->getMessage(),
                'data'      => $exception,
            ];
            $this->app['trace']->error(TraceCode::SHIPPING_SERVICE_ERROR, $data);
            $this->trace->count(Metric::SHIPPING_SERVICE_CALL_FAILURE_COUNT, array_merge(
                $input,
                [
                    'merchant_id' => $merchantId,
                    'error' => $data
                ]
            ));
        }
        catch (\Exception $exception)
        {
            $data = [
                'exception' => $exception->getMessage(),
                'data'      => $exception,
            ];
            $this->app['trace']->error(TraceCode::SHIPPING_SERVICE_ERROR, $data);
            $result["serviceable"] = true;
            $this->trace->count(Metric::SHIPPING_SERVICE_CALL_FAILURE_COUNT, array_merge(
                $input,
                [
                    'merchant_id' => $merchantId,
                    'error' => $data
                ]
            ));
        }
        return $result;
    }


}
