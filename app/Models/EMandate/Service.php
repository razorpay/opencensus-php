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
    /**
     * @param string $gateway
     * @param array  $input The input received from the route
     *
     * @return array Summary of reconciliation
     * @throws \Throwable
     */
    public function reconcileRegistrationFile(string $gateway, array $input)
    {
        $this->trace->info(
            TraceCode::EMANDATE_REGISTER_RECON_REQUEST,
            ['gateway'   => $gateway]);

        (new Validator)->validateRegistrationGateway($gateway);

        $response = $this->app['gateway']->call(
                            $gateway,
                            Payment\Action::RECONCILE_REGISTER_EMANDATE,
                            $input,
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
}
