<?php

namespace RZP\Gateway\Netbanking;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;

class Service extends Base\Service
{
    public function generateRegistrationFile(string $gateway, array $input): array
    {
        (new Validator)->validateGateway($gateway);

        list($from, $to) = $this->getTimestamps($input);

        $payments = $this->repo->payment->fetchPendingEMandateRegistration($gateway, $from, $to);

        if ($payments->count() === 0)
        {
            return ['count' => 0, 'message' => 'No payments are pending EMandate registration for gateway ' . $gateway];
        }

        $gatewayInput = ['payments' => $payments];

        $response = $this->app['gateway']->call($gateway, Payment\Action::INITIATE_REGISTER_EMANDATE, $gatewayInput, $this->mode);

        return $response;
    }

    public function reconcileRegistrationFile(stirng $gateway, array $input)
    {
        (new Validator)->validateGateway($gateway);

        $response = $this->app['gateway']->call($gateway, Payment\Action::RECONCILE_REGISTER_EMANDATE, $input, $this->mode);

        return $response;
    }

    public function generateDebitFile(string $gateway, array $input)
    {
        (new Validator)->validateGateway($gateway);

        list($from, $to) = $this->getTimestamps($input);

        $payments = $this->repo->payment->fetchPendingEMandateDebit($gateway, $from, $to);

        if ($payments->count() === 0)
        {
            return ['count' => 0, 'message' => 'No payments are pending EMandate debit for gateway ' . $gateway];
        }

        $gatewayInput = ['payments' => $payments];

        $response = $this->app['gateway']->call($gateway, Payment\Action::INITIATE_DEBIT_EMANDATE, $gatewayInput, $this->mode);

        return $response;
    }

    public function reconcileDebitFile(stirng $gateway, array $input)
    {
        (new Validator)->validateGateway($gateway);

        $response = $this->app['gateway']->call($gateway, Payment\Action::RECONCILE_DEBIT_EMANDATE, $input, $this->mode);

        return $response;
    }

    protected function getTimestamps(array $input): array
    {
        if ((isset($input['from']) === true) and (isset($input['to']) === true))
        {
            return [$input['from'], $input['to']];
        }

        // Default to yesterday's timestamps
        $from = Carbon::yesterday(Timezone::IST)->timestamp;

        $to = Carbon::today(Timezone::IST)->timestamp - 1;

        return [$from, $to];
    }
}