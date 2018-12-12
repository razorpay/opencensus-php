<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    const ACQUIRER = null;

    /**
     * Used in Mock\GatewayTrait, but defined here because
     * traits can't define constants
     */
    const MOCK_ROUTE    = 'mock_upi_payment';

    protected function createGatewayPaymentEntity($attributes, $action = null)
    {
        $attr = $this->getMappedAttributes($attributes);

        $entity = $this->getNewGatewayPaymentEntity();

        $action = $action ?? $this->action;

        switch ($action)
        {
            case Base\Action::REFUND:

                $entity->setRefundId($this->input['refund']['id']);

                $entity->setAmount($this->input['refund']['amount']);

                $entity->setPaymentId($this->input['payment']['id']);

                break;

            case Base\Action::PAYOUT:

                $entity->setPaymentId($this->input['gateway_input']['ref_id']);

                $entity->setAmount($this->input['gateway_input']['amount']);

                break;

            default:
                $entity->setAmount($this->input['payment']['amount']);

                $entity->setPaymentId($this->input['payment']['id']);
        }

        $entity->setAction($action);

        $entity->setAcquirer(static::ACQUIRER);

        $entity->setGateway($this->gateway);

        $entity->generate($attr);

        $entity->fill($attr);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function generateIntentString(array $content)
    {
        $query = str_replace(' ', '', urldecode(http_build_query($content)));

        return 'upi://pay?' . $query;
    }

    /*
     * * * * * * * * * * SIGNED INTENT * * * * * * * * * * * *
     */

    /**
     * We are using SI Private Key Check as SI is migration change.
     * Once we move all terminals to SI, this check can be removed.
     *
     * @return bool
     */
    protected function shouldSignIntentRequest(): bool
    {
        return (empty($this->getSignIntentPrivateKey()) === false);
    }

    /**
     * Private key is merchant dependent, thus can only be retrieved
     * from terminal, Starting with MindGate where it's store in
     * gateway_terminal_password2, Later gateways can override this.
     *
     * @return mixed
     */
    protected function getSignIntentPrivateKey()
    {
        return $this->terminal['gateway_terminal_password2'];
    }

    /**
     * Create an instance of Secure modes in UPI which are SI and SQR
     * Method must not be overridden, if there are gateway specific
     * changes required, Add a getter and override that.
     *
     * @return Secure
     */
    protected function getSecureInstance(): Secure
    {
        $config = [
            Secure::PRIVATE_KEY => $this->getSignIntentPrivateKey(),
        ];

        $secure = new Secure($config);

        return $secure;
    }
}
