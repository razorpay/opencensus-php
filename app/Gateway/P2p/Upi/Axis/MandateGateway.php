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

        $callback = $this->handleSdkCallback(false);

        $mandate = $this->input->get(Entity::MANDATE);

        $transformer = new UpiMandateTransformer($sdk->toArray(), $callback->get(Fields::ACTION));

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

    protected function getMerchantRequestId($mandate)
    {
        return 'RZP' . str_pad($mandate->get(Entity::ID), 32, '0', STR_PAD_LEFT);
    }
}
