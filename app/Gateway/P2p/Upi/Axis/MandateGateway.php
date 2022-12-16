<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiMandateTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\MandateRequestTransformer;
use RZP\Gateway\P2p\Upi\Axis\Actions\MandateAction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\MandateTransformer;

/**
 * Class MandateGateway
 *
 * @package RZP\Gateway\P2p\Upi\Sharp
 * Mandate Gateway defintion for sharp gateway
 */
class MandateGateway extends Gateway implements Contracts\MandateGateway
{
    protected $actionMap = MandateAction::MAP;
    /**
     * This is the method to create credential request
     * @param Response $response
     */
    public function initiatePay(Response $response)
    {

    }

    /**
     * This is the method to initiate authorize mandate flow
     * @param Response $response
     *
     */
    public function initiateAuthorize(Response $response)
    {
        $transformer = new MandateRequestTransformer($this->input->toArray());

        $transformer->put('context', [
            'handle_code'   => $this->getContextHandleCode()
        ]);

        $action = MandateAction::APPROVE_DECLINE_MANDATE;

        $transformer->put(Fields::ACTION,$action);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::REQUEST_TYPE,MandateAction::APPROVE);

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());

        $request->mergeUdf($transformer->transformUdf());

        $response->setRequest($request);
    }

    /**
     * This is the method to authorize mandate
     * @param Response $response
     */
    public function authorizeMandate(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $callback = $this->handleSdkCallback(true);

        $mandate = $this->input->get(Entity::MANDATE);

        $transformer = new UpiMandateTransformer($sdk->toArray(), $sdk->get(Fields::GATEWAY_RESPONSE_STATUS));

        $transformer->put(Fields::MERCHANT_REQUEST_ID, $this->getMerchantRequestId($mandate));

        $upi = $transformer->transformSdk();

        $transformer = new MandateTransformer($upi, $callback->get(Fields::ACTION));

        $mandate = $transformer->transformSdk();

        if(isset($upi[Entity::MANDATE]))
        {
            unset($upi[Entity::MANDATE]);
        }

        $mandate[Entity::UPI] = $upi;

        $response->setData([
               Entity::MANDATE => $mandate
           ]);

    }

    /**
     * This is the method to initiate reject response
     * @param Response $response
     */
    public function initiateReject(Response $response)
    {
        $transformer = new MandateRequestTransformer($this->input->toArray());

        $transformer->put('context', [
            'handle_code'   => $this->getContextHandleCode()
        ]);

        $action = MandateAction::APPROVE_DECLINE_MANDATE;

        $transformer->put(Fields::ACTION,$action);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::REQUEST_TYPE,MandateAction::DECLINE);

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());

        $request->mergeUdf($transformer->transformUdf());

        $response->setRequest($request);
    }

    /**
     * This is the method to initiate pause response
     * @param Response $response
     */
    public function initiatePause(Response $response)
    {
        $transformer = new MandateRequestTransformer($this->input->toArray());

        $transformer->put('context', [
            'handle_code'   => $this->getContextHandleCode()
        ]);

        $action = MandateAction::PAUSE_UNPAUSE_MANDATE;

        $transformer->put(Fields::ACTION, $action);
        $transformer->put(Fields::REQUEST_TYPE, MandateAction::PAUSE);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());

        $response->setRequest($request);
    }

    /**
     * This is the function to pause the mandate
     */
    public function pause(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $callback = $this->handleSdkCallback(true);

        $mandate = $this->input->get(Entity::MANDATE);

        $transformer = new UpiMandateTransformer($sdk->toArray(), MandateAction::PAUSED);

        $transformer->put(Fields::MERCHANT_REQUEST_ID, $this->getMerchantRequestId($mandate));

        $upi = $transformer->transformSdk();

        $transformer = new MandateTransformer($upi, $callback->get(Fields::ACTION));

        $mandate = $transformer->transformSdk();

        if(isset($upi[Entity::MANDATE]))
        {
            unset($upi[Entity::MANDATE]);
        }

        $mandate[Entity::UPI] = $upi;

        $response->setData([
           Entity::MANDATE => $mandate
        ]);

    }

    /**
     * This is the method to initiate unpause response
     * @param Response $response
     */
    public function initiateUnPause(Response $response)
    {
        $transformer = new MandateRequestTransformer($this->input->toArray());

        $transformer->put('context', [
            'handle_code'   => $this->getContextHandleCode()
        ]);

        $action = MandateAction::PAUSE_UNPAUSE_MANDATE;

        $transformer->put(Fields::ACTION, $action);
        $transformer->put(Fields::REQUEST_TYPE, MandateAction::UNPAUSE);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());
        $response->setRequest($request);
    }

    /**
     * This is the function to unpause the mandate
     */
    public function unpause(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $callback = $this->handleSdkCallback(true);

        $mandate = $this->input->get(Entity::MANDATE);

        $transformer = new UpiMandateTransformer($sdk->toArray(), MandateAction::UNPAUSED);

        $transformer->put(Fields::MERCHANT_REQUEST_ID, $this->getMerchantRequestId($mandate));

        $upi = $transformer->transformSdk();

        $transformer = new MandateTransformer($upi, $callback->get(Fields::ACTION));

        $mandate = $transformer->transformSdk();

        if(isset($upi[Entity::MANDATE]))
        {
            unset($upi[Entity::MANDATE]);
        }

        $mandate[Entity::UPI] = $upi;

        $response->setData([
           Entity::MANDATE => $mandate
        ]);

    }

    /**
     * This is the method to initiate revoke response
     * @param Response $response
     */
    public function initiateRevoke(Response $response)
    {
        $transformer = new MandateRequestTransformer($this->input->toArray());

        $transformer->put('context', [
            'handle_code'   => $this->getContextHandleCode()
        ]);

        $action = MandateAction::UPDATE_OR_REVOKE_MANDATE;

        $transformer->put(Fields::ACTION,$action);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::REQUEST_TYPE, MandateAction::REVOKE);

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());

        $request->mergeUdf($transformer->transformUdf());

        $response->setRequest($request);
    }

    /**
     * This is the function to unpause the mandate
     */
    public function revoke(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $callback = $this->handleSdkCallback(true);

        $mandate = $this->input->get(Entity::MANDATE);

        $transformer = new UpiMandateTransformer($sdk->toArray(), MandateAction::REVOKED);

        $transformer->put(Fields::MERCHANT_REQUEST_ID, $this->getMerchantRequestId($mandate));

        $upi = $transformer->transformSdk();

        $transformer = new MandateTransformer($upi, $callback->get(Fields::ACTION));

        $mandate = $transformer->transformSdk();

        if(isset($upi[Entity::MANDATE]))
        {
            unset($upi[Entity::MANDATE]);
        }

        $mandate[Entity::UPI] = $upi;

        $response->setData([
               Entity::MANDATE => $mandate
        ]);
    }

    protected function getMerchantRequestId($mandate)
    {
        return 'RAZORPAY' . str_pad($mandate->get(Entity::ID), 27, '0', STR_PAD_LEFT);
    }
}
