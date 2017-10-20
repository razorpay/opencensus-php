<?php

namespace RZP\Models\EMandate;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function generateRegistrationFile(string $gateway, array $input): array
    {
        // Validate bank
        (new Validator)->validateRegistrationGateway($gateway);

        // Get timestamps
        list($from, $to) = $this->getTimestamps($input);

        // Get eligible payments
        $payments = $this->repo->payment->fetchPendingEMandateRegistration($gateway, $from, $to);

        if ($payments->count() === 0)
        {
            return [
                'count' => 0,
                'message' => 'No payments are pending EMandate registration for gateway ' . $gateway
            ];
        }

        $gatewayInput = ['payments' => $payments];

        // Call gateway method
        $response = $this->app['gateway']->call(
                            $gateway,
                            Payment\Action::INITIATE_REGISTER_EMANDATE,
                            $gatewayInput,
                            $this->mode);

        return $response;
    }

    /**
     * @param string $gateway
     * @param array  $input The input received from the route
     *
     * @return array Summary of reconciliation
     * @throws \Throwable
     */
    public function reconcileRegistrationFile(string $gateway, array $input)
    {
        (new Validator)->validateRegistrationGateway($gateway);

        $response = $this->app['gateway']->call(
                            $gateway,
                            Payment\Action::RECONCILE_REGISTER_EMANDATE,
                            $input,
                            $this->mode);


        return $response;
    }

    public function generateDebitFile(string $gateway, array $input)
    {
        (new Validator)->validateDebitGateway($gateway);

        list($from, $to) = $this->getTimestamps($input);

        $payments = $this->repo->payment->fetchPendingEMandateDebit($gateway, $from, $to);

        if ($payments->count() === 0)
        {
            return ['count' => 0, 'message' => 'No payments are pending EMandate debit for gateway ' . $gateway];
        }

        $gatewayInput = ['payments' => $payments];

        $response = $this->app['gateway']->call(
                            $gateway,
                            Payment\Action::INITIATE_DEBIT_EMANDATE,
                            $gatewayInput,
                            $this->mode);

        return $response;
    }

    public function reconcileDebitFile(string $gateway, array $input)
    {
        (new Validator)->validateDebitGateway($gateway);

        $response = $this->app['gateway']->call(
                            $gateway,
                            Payment\Action::RECONCILE_DEBIT_EMANDATE,
                            $input,
                            $this->mode);

        return $response;
    }

    protected function getTimestamps(array $input): array
    {
        if ((isset($input['from']) === true) and
            (isset($input['to']) === true))
        {
            return [$input['from'], $input['to']];
        }

        // Default to yesterday's timestamps
        $from = Carbon::yesterday(Timezone::IST)->timestamp;

        $to = Carbon::today(Timezone::IST)->timestamp - 1;

        return [$from, $to];
    }
}
