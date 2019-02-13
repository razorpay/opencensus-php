<?php

namespace RZP\Models\Terminal\Auth;

use App;

use RZP\Models\Payment;
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

    public function getValidAuths(): array
    {
        $validAuths = [];

        foreach ($this->auths as $auth)
        {
            if ($this->isValidAuth($auth) === true)
            {
                $validAuths[] = $auth;
            }
        }

        return $validAuths;
    }


    public function getAuthenticationTerminals(): array
    {
        $validAuths = $this->getValidAuths();

        $traceData = [
            'valid_auths' => $validAuths,
        ];

        $this->trace->info(TraceCode::AUTH_SELECTION_VALID_AUTHS, $traceData);

        $authTerminals = AuthTerminals::AUTHENTICATION_TERMINALS;

        $selectedAuthTerminals = [];

        $gateway = $this->payment->terminal->gateway;

        foreach ($validAuths as $auth)
        {
            $terminal = array_filter(
                            $authTerminals,
                            function ($terminal) use ($gateway, $auth)
                            {
                                if (($terminal[AuthTerminals::GATEWAY] === $gateway) and
                                    ($terminal[AuthTerminals::AUTH_TYPE] === $auth))
                                {
                                    return true;
                                }

                                return false;
                            });

            if (empty($terminal) === false)
            {
                /*
                 * head(array_values($terminal)), in the result we get a map with
                 * index as key and terminal as value
                 */
                array_push($selectedAuthTerminals, head(array_values($terminal)));
            }
        }

        return $selectedAuthTerminals;
    }

    abstract function isValidAuth($authType): bool;

    public function setAuthsApplicableForMethod()
    {
        $authType = $this->payment->getAuthType() ?? Payment\AuthType::UNKNOWN ;

        $method = $this->payment->getMethod();

        if (empty(Payment\AuthType::DEFAULT_AUTH_ORDER[$method][$authType]) === false)
        {
            $this->auths = Payment\AuthType::DEFAULT_AUTH_ORDER[$method][$authType];
        }
    }
}
