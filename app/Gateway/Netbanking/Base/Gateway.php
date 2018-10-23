<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;

class Gateway extends \RZP\Gateway\Base\Gateway
{
    /**
     * Tells us whether the gateway is being used in tpv mode or not
     * @var bool
     */
    protected $tpv;

    /**
     * Tells us whether the Gateway is being used in Retail, Corporate or EMandate modes
     * @var string
     */
    protected $bankingType;

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setBank($this->input['payment']['bank']);

        if (($this->action === Action::AUTHORIZE) and
            ($this->input['merchant']->isTPVRequired()))
        {
            $gatewayPayment->setAccountNumber($this->input['order']['account_number']);
        }

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
    }

    protected function getRepository()
    {
        $gateway = 'netbanking';

        return $this->app['repo']->$gateway;
    }

    protected function setTpv(Entity $gatewayPayment)
    {
        $this->tpv = $gatewayPayment->isTpv();
    }

    public function isPaymentTpvEnabled(Entity $gatewayPayment, Merchant\Entity $merchant)
    {
        if (($gatewayPayment->isTpv()) or ($merchant->isTPVRequired()))
        {
            return true;
        }

        return false;
    }

    public function generateClaims(array $input)
    {
        $paymentIds = array_map(function($row)
        {
            return $row['payment']['id'];
        }, $input['data']);

        $gatewayPayments = $this->repo->fetchByPaymentIdsAndAction(
                                $paymentIds, Action::AUTHORIZE);

        // payment id is key and gatewayPayment entity is value
        $gatewayPayments = $gatewayPayments->getDictionaryByAttribute(Entity::PAYMENT_ID);

        // Adding relevant information to each gateway row in $input['data']
        $input['data'] = array_map(function($row) use ($gatewayPayments)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayPayments[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayPayments[$paymentId]->toArray();
            }

            return $row;
        }, $input['data']);

        $namespace = $this->getGatewayNamespace();

        $class = $namespace . '\\' . 'ClaimsFile';

        return (new $class)->generate($input);
    }

    public function initiateRegisterEmandate(array $input)
    {
        $this->input = $input;
    }

    public function reconcileRegisterEmandate(array $input)
    {
        $this->input = $input;
    }

    public function initiateDebitEmandate(array $input)
    {
        $this->input = $input;
    }

    public function reconcileDebitEmandate(array $input)
    {
        $namespace = $this->getGatewayNamespace();

        $class = $namespace . '\\' . 'EMandateDebitReconFile';

        return (new $class)->process($input);
    }

    public function setBankingType(string $bankingType)
    {
        $this->bankingType = $bankingType;
    }

    protected function setCorporateBanking()
    {
        $this->setBankingType(BankingType::CORPORATE);
    }

    protected function isCorporateBanking()
    {
        return ($this->getBankingType() === BankingType::CORPORATE);
    }

    protected function isRetailBanking()
    {
        return ($this->getBankingType() === BankingType::RETAIL);
    }

    protected function getBankingType()
    {
        return $this->bankingType;
    }

    protected function shouldStatusBeUpdated(Entity $gatewayPayment)
    {
        //
        // If the authorize status is set to Y,
        // we are not saving the verify response status
        //
        if ((isset($gatewayPayment[Entity::STATUS]) === true) and
            ($gatewayPayment[Entity::STATUS] === $this->getAuthSuccessStatus()))
        {
            return false;
        }

        return true;
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $gatewayPayment->getBankPaymentId()
            ]
        ];
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
        array $extraData = [])
    {
        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'extra_data' => $extraData
            ]);
    }
}
