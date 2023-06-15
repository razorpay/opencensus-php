<?php

namespace RZP\Models\Terminal\Auth;

use App;

use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\AuthenticationTerminals as AuthTerminals;

abstract class Base
{
    protected $auths = [];

    protected $payment;

    protected $merchant;

    protected $trace;

    public function __construct(Payment\Entity $payment)
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;

        $this->merchant = $payment->merchant;

        $this->setAuthsApplicableForMethod();

        $this->trace = $this->app['trace'];
    }

    public function getValidAuths($authenticationGateways=[]): array
    {
        $payment = $this->payment;

        if ($payment->getGateway() === Gateway::AXIS_TOKENHQ) {
            return [Payment\AuthType::OTP];
        }
        if ($payment->getGateway() === Gateway::ICICI) {
            return [Payment\AuthType::OTP];
        }

        $validAuths = [];

        foreach ($this->auths as $auth)
        {
            if ($this->isValidAuth($auth, $authenticationGateways) === true)
            {
                $validAuths[] = $auth;
            }
        }

        return $validAuths;
    }

    public function getAuthenticationTerminals($terminals): array
    {
        $authenticationGateways = array_unique(array_pluck($terminals, 'authentication_gateway'));

        $validAuths = $this->getValidAuths($authenticationGateways);

        $traceData = [
            'valid_auths' => $validAuths,
        ];

        $this->trace->info(TraceCode::AUTH_SELECTION_VALID_AUTHS, $traceData);

        $selectedAuthTerminals = [];

        foreach ($validAuths as $auth)
        {
            $terminal = array_filter(
                            $terminals,
                            function ($terminal) use ($auth)
                            {
                                if ($terminal[AuthTerminals::AUTH_TYPE] === $auth)
                                {
                                    return true;
                                }

                                return false;
                            });

            if (empty($terminal) === false)
            {
                /*
                 * in the result we get a map with
                 * index as key and terminal as value
                 */
                 $selectedAuthTerminals = array_merge($selectedAuthTerminals, array_values($terminal));
            }
        }

        return $selectedAuthTerminals;
    }

    abstract function isValidAuth($authType, $authenticationGateways): bool;

    public function setAuthsApplicableForMethod()
    {
        $authType = $this->payment->getAuthType() ?? Payment\AuthType::UNKNOWN;

        $method = $this->payment->getMethod();

        if (empty(Payment\AuthType::DEFAULT_AUTH_ORDER[$method][$authType]) === false)
        {
            $this->auths = Payment\AuthType::DEFAULT_AUTH_ORDER[$method][$authType];
        }
    }
}
