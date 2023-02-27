<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Action;

trait Omnichannel
{
    protected function canRunOmnichannelFlow(Payment\Entity $payment)
    {
        if ($payment->isUpi() === false)
        {
            return false;
        }

        if ($payment->hasTerminal() === true)
        {
            // skip upi omnichannel flow for Optimizer payments
            $terminalTypeArray = array();

            $terminalTypeArray = $payment->terminal->getType();

            if (($terminalTypeArray !== null) && (in_array('optimizer', $terminalTypeArray) === true))
            {
                 $this->trace->info(TraceCode::OPTIMIZER_PAYMENT_SKIPPING_UPI_OMNICHANNEL,
                 [
                    'payment_id' => $payment->getId(),
                    'terminal_id' => $payment->terminal->getId(),
                 ]);

                return false;
            }
        }

        $upiProvider = $payment->getMetadata(Payment\Entity::UPI_PROVIDER, null);

        if ($upiProvider !== null)
        {
            return true;
        }

        return false;
    }

    protected function runOmnichannelFlow(Payment\Entity $payment, array $request)
    {
        try
        {
            $upiProvider = $payment->getMetadata(Payment\Entity::UPI_PROVIDER, null);

            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_AUTHENTICATION_OMNICHANNEL_REQUEST_INITIATED,
                $payment,
                null,
                [],
                [
                    'upi_provider' => $upiProvider
                ]);

            $terminal = $this->repo->terminal->fetchForPayment($this->payment);

            $vpa = $terminal->getVpaForTerminal();

            $gateway = Payment\UpiProvider::$upiProvidersToGatewayMap[$upiProvider];

            $gatewayData['gateway'] = $gateway;

            $gatewayData['payment'] = $this->payment;

            $gatewayData['upi'] = [];

            $gatewayData['upi']['tr'] = $this->getTrFromIntent($request['data']['intent_url']);

            $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, ['vpa' => $vpa, 'enabled' => true]);

            $gatewayData['terminal'] = $terminal;

            $gatewayData['merchant'] = $this->payment->merchant;

            $gatewayData['merchant_detail'] = $this->repo->merchant_detail->fetchForMerchant($this->payment->merchant);

            $this->callOmniPayGatewayFunction($gatewayData, $gateway, $terminal);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_OMNICHANNEL_REQUEST_PROCESSED, $payment);

            return $request;
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_OMNICHANNEL_REQUEST_PROCESSED, $payment, $ex);

            throw $ex;
        }
    }

    protected function getTrFromIntent(string $url): string
    {
        $parts = parse_url($url);

        $query = [];

        parse_str($parts['query'], $query);

        return $query['tr'];
    }

    protected function callOmniPayGatewayFunction(array $gatewayData, $gateway, $terminal)
    {
        $this->addGatewayConfig($gatewayData);

        try
        {
            return $this->app['gateway']->call($gateway, Payment\Action::OMNI_PAY, $gatewayData, $this->mode, $terminal);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            $error = $ex->getError();

            throw $ex;
        }
    }
}
