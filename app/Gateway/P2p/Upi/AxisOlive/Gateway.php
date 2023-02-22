<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Constants\Entity;
use RZP\Trace\TraceCode;
use RZP\Gateway\P2p\Upi;
use RZP\Gateway\P2p\Upi\AxisOlive\ErrorMap;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Gateway\P2p\Upi\AxisOlive\S2sDirect;
use RZP\Exception\P2p\GatewayErrorException;

/**
 * Class Gateway
 * Common gateway class which will be used for communication with gateway
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class Gateway extends Upi\Gateway
{
    protected $actionMap                    = [];

    protected $gateway                      = Entity::P2M_UPI_AXIS_OLIVE;

    protected $mozart_gateway_resource      = Entity::UPI_AXISOLIVE;

    protected function getTimeStamp()
    {
        return (string) ($this->getCurrentTimestamp() * 1000);
    }

    /***
     * This is the method to get merchant id from client data
     * @return \Illuminate\Support\TGetDefault|\Illuminate\Support\TValue
     */
    protected function getMerchantId()
    {
        return $this->getClientGatewayData()->get(Fields::MERCH_ID);
    }

    /**
     * This is the method to get merchant Channel id from client data
     * @return \Illuminate\Support\TGetDefault|\Illuminate\Support\TValue
     */
    protected function getMerchantChannelId()
    {
        return $this->getClientGatewayData()->get(Fields::MERCH_CHANNEL_ID);
    }

    /**
     * This is the method to get sub merchant id from client data
     * @return \Illuminate\Support\TGetDefault|\Illuminate\Support\TValue
     */
    protected function getSubMerchantId()
    {
        return $this->getClientGatewayData()->get(Fields::SUB_MERCH_ID);
    }

    /**
     * This is the method to get merchant category code from client data
     * @return \Illuminate\Support\TGetDefault|\Illuminate\Support\TValue
     */
    protected function getMerchantCategoryCode()
    {
        return $this->getClientGatewayData()->get(Fields::MCC);
    }

    /**
     * This is a method to get entity
     * @return string
     */
    protected function getEntity()
    {
        $action = strtr(static::class, ['RZP\Gateway\P2p\Upi\AxisOlive\\' => '', 'Gateway' => '']);

        return snake_case($action);
    }

    /**
     * This is the method to get mozart resource URL
     * @param $input
     * @param $mozartResourceUrl
     *
     * @return string
     */
    protected function getResourceUrl($input ,$mozartResourceUrl)
    {
        $urlConfig = 'applications.mozart.' . $this->mode . '.url';

        $baseUrl = $this->app['config']->get($urlConfig);

        $version = $this->getVersionForAction($input, $this->action);

        return $baseUrl . 'upiPayments/' . $this->mozart_gateway_resource . '/' . $version . '/' . snake_case($mozartResourceUrl);
    }

    /**
     * This is the method to send mozart request and forward response
     * @param S2s $s2sRequest
     *
     * @return mixed
     */

    protected function sendGatewayRequestAndParseResponse(S2s $s2sRequest)
    {
        // get mozart password config
        $passwordConfig = 'applications.mozart.' . $this->mode . '.password';

        $authentication = [
            'api',
            $this->app['config']->get($passwordConfig)
        ];

        $request = $s2sRequest->finish();

        $request['headers'] = [
            'Content-Type'  => 'application/json',
            'X-Task-ID'     => $this->app['request']->getTaskId(),
        ];

        $request['options'] = [
            'auth' => $authentication
        ];

        $entity = $this->getEntity();

        $this->trace->info(TraceCode::TURBO_GATEWAY_REQUEST, [
            'action'        => $this->action,
            'url'           => $request['url'],
            'entity'        => $entity,
            'gateway'       => $this->gateway,
            'mock'          => $this->mock,
            'mozart_content'=> $this->maskUpiAxisRequest($request),
        ]);

        $response =  parent::sendGatewayRequest($request);

        $response = $s2sRequest->response($response);

        $this->trace->info(TraceCode::TURBO_GATEWAY_RESPONSE, [
            'action'    => $this->action,
            'entity'    => $entity,
            'gateway'   => $this->gateway,
            'mock'      => $this->mock,
            'response'  => $response,
        ]);

        return $response;
    }

    /**
     * Initiate S2S request with target gateway and target resource
     * @param string $action
     *
     * @return S2sMozart
     */
    protected function initiateS2sRequest(string $action)
    {
        $map = $this->actionMap[$action];

        $accessor = function(string $method)
        {
            // getMerchantId(),getMerchantChannelId(),getTimeStamp() is being called
            return $this->{$method}();
        };

        $request = null;

        switch ($map[Actions\Action::SOURCE])
        {
            case Actions\Action::MOZART:
                $request = new S2sMozart($accessor, $this->getResourceUrl($action , $map[Actions\Action::MOZART][Actions\Action::RESOURCE]));
                break;
        }

        return $request;
    }

    /**
     * This is the method to mask fields from logs
     * @param array $request
     *
     * @return array
     */
    protected function maskUpiAxisRequest(array $request)
    {
        $content = json_decode(array_pull($request, 'content'), true);

        $keys = [
            CustomerEntity::CONTACT => Fields::MOBILE_NUMBER,
        ];

        $masked = $this->maskUpiDataForTracing($content, $keys);

        $request['content'] = $masked;

        return $request;
    }

    protected function p2pGatewayException(
        string $gatewayCode,
        array $data = [],
        string $gatewayDesc = null)
    {
        $code = ErrorMap::map($gatewayCode);

        $data[Fields::ENTITY] = $this->getEntity();
        $data[Fields::ACTION] = $this->getAction();

        return new GatewayErrorException($code, $gatewayCode, $gatewayDesc, $data);
    }
}
