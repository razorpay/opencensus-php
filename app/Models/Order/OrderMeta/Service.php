<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Merchant\ShippingInfo;
use RZP\Models\Merchant\Metric;
use RZP\Trace\TraceCode;

class Service extends \RZP\Models\Base\Service
{
    /**
     * Function to update customer details for 1CC Orders.
     * @param string $orderId
     * @param array $input
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */
    public function updateCustomerDetailsFor1CCOrder(string $orderId, array $input): array
    {
        $startTime = millitime();
        $dimensions = [
            "mode" => $this->mode
        ];

        $ex = [];
        $result = [];

        try {
            $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_COUNT, $dimensions);

            try {
                (new Order1cc\Validator())->validateInput('editCustomerDetails', $input);
                (new Core)->validateActive1CCOrderId($orderId);
            } catch (\Throwable $e) {
                $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_ERROR_COUNT, $dimensions);
                $ex = $e;
                throw $e;
            }

            $orderMetaInput = [];
            $customerInfo = $input[Order1cc\Fields::CUSTOMER_DETAILS];
            if (isset($customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]) === true) {
                $address[0] = [
                    "zipcode" => $customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]['zipcode'],
                    "country" => $customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]['country']];
                $shippingInfoReq = [
                    'order_id' => $orderId,
                    'addresses' => $address,
                ];

                try {

                    $addresses = (new ShippingInfo\Service())->getShippingInfo($shippingInfoReq);
                    $shippingInfo = $addresses['addresses'][0];

                } catch (\Throwable $e) {
                    $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_FAULT_COUNT, $dimensions);
                    $ex = new BadRequestException(ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND);
                    throw $ex;
                }
                if ($shippingInfo === null or
                    $shippingInfo['serviceable'] === false) {
                    $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_FAULT_COUNT, $dimensions);
                    $ex = new BadRequestException(ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND);
                    throw $ex;
                }

                $orderMetaInput = [
                    Order1cc\Fields::COD_FEE => $shippingInfo[Order1cc\Fields::COD_FEE] ?? 0,
                    Order1cc\Fields::SHIPPING_FEE => $shippingInfo[Order1cc\Fields::SHIPPING_FEE] ?? 0,
                ];
            }

            $orderMetaInput = array_merge($orderMetaInput, [
                Order1cc\Fields::CUSTOMER_DETAILS => $customerInfo,
            ]);

            $result = (new Core)->update1CCOrder($orderId, $orderMetaInput);

            $duration = millitime() - $startTime;
            $this->trace->histogram(Metric::UPDATE_CUSTOMERS_DETAILS_TIME_MILLIS, $duration, $dimensions);

            return $result;
        } finally {
            $this->traceUpdateCustomerDetailsLogs($orderId, $input, $result, $ex);
        }
    }

    /**
     * Resets fee details and applied promotions for the order in case the orderId is re-used.
     * Applies only for 1CC Orders.
     * @param string $orderId
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */
    public function reset1CCOrder(string $orderId)
    {
        $core = (new Core);
        $core->validateActive1CCOrderId($orderId);

        $core->update1CCOrder(
            $orderId,
            [
                Order1cc\Fields::COD_FEE      => 0,
                Order1cc\Fields::SHIPPING_FEE => 0,
                Order1cc\Fields::PROMOTIONS   => [],
            ]);
    }

    protected function getShippingInfoCacheKey($orderId, $address): string
    {
        $merchantId = $this->merchant !== null ? $this->merchant->getId() : "";
        $zipcode = $address['zipcode'] ?? "";
        return "SHIPPING_INFO_"
            . $merchantId
            . "_"
            . $orderId
            . "_"
            . $zipcode
            . "_"
            . $address['country'];
    }

    protected function traceUpdateCustomerDetailsLogs($orderId, $input, $response, $ex) {
        $input = array_merge(
            $input,
            [
                'order_id' => $orderId
            ]
        );

        if (empty($ex) === true){
            $this->trace->info(TraceCode::UPDATE_CUSTOMERS_DETAILS_REQUEST,
                [
                    'request' =>  $this->maskCustomerDetails($input),
                    'response'=>  $this->maskCustomerDetails($response),
                    'exception'=> $ex
                ]
            );
        }else {
            $this->trace->error(TraceCode::UPDATE_CUSTOMERS_DETAILS_REQUEST_ERROR,
                [
                    'request' =>  $this->maskCustomerDetails($input),
                    'response'=>  $this->maskCustomerDetails($response),
                    'exception'=> $ex->getTrace()
                ]
            );
        }
    }

    protected function maskCustomerDetails($input) {
        $maskedRequest =[];

        if (empty($input['order_id']) === false) {
            $maskedRequest = array_merge($maskedRequest,
                [
                    "order_id" => $input['order_id']
                ]);
        }


        if (empty($input['customer_details']) === false) {

            $customerInfo = $input['customer_details'];
            if (empty($customerInfo['shipping_address']) === false && empty($customerInfo['shipping_address']['line1']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'address_line_1' => mask_by_percentage($customerInfo['shipping_address']['line1'])
                    ]);
            }
            if (empty($customerInfo['shipping_address']) === false && empty($customerInfo['shipping_address']['line2']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'address_line_2' => mask_by_percentage($customerInfo['shipping_address']['line2'])
                    ]);
            }


            if (empty($customerInfo['contact']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'contact' => mask_phone($customerInfo['contact'])
                    ]);
            }
            if (empty($customerInfo['email']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'email' => mask_email($customerInfo['email'])]
                );
            }
        }

        return $maskedRequest;
    }

}
