<?php

namespace RZP\Models\GatewayStatus\Absence\CallbackProcessor;

use App;
use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\GatewayStatus\Absence\Entity;
use RZP\Models\GatewayStatus\Absence\ReasonCode;
use RZP\Models\GatewayStatus\Absence\Processor;
use RZP\Models\Base\Core;

class StatusCakeProcessor extends Core implements AbstractProcessorInterface
{
    protected $processor;

    const STATUS_UP = 'UP';

    const STATUS_DOWN = 'DOWN';

    protected function fetchStatusCakeCredentials()
    {
        $uname = $this->app['config']->get('gateway.absence.statuscake.username');
        
        $apiKey = $this->app['config']->get('gateway.absence.statuscake.api_key');
        
        return [$uname, $apiKey];
    }

    public function process(array $input)
    {
        try
        {
            $this->validateRequest($input);

            $sStatus = strtoupper($input['Status']);
            
            $status = null;

            if (in_array($sStatus, [self::STATUS_UP, self::STATUS_DOWN]))
            {
                $status = ($sStatus === self::STATUS_UP) ? 1 : 0;    
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException('Invalid Status : '. $sStatus);
            }
            
            $data = $this->formatInput($input, $status);

            if ($status === 1)
            {
                $absent = $this->processor->verifyIfExists($data);

                if (empty($absent) === false)
                {
                    $editData = [
                        Entity::FROM => $absent->getFrom(),
                        Entity::TO   => time()
                    ];

                    return $this->processor->editAction($absent->id, $editData);
                }
            }
            else
            {
                // this is a down, create a new entry. Unlikely that status cake might send duplicate down
                // events for the same url.
                return $this->processor->createAction($data);
            }
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }

    protected function validateRequest(array $input)
    {
        if (isset($input["Token"]) === false)
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCODE_MISSING_TOKEN, ['data' => $input]);

            throw new Exception\BadRequestValidationFailureException('StatusCake Token Missing');
        }

        $this->validateToken($input);
    }

    protected function validateToken(array $input)
    {
        $token = $input['Token'];
        
        list($uname, $apiKey) = $this->fetchStatusCakeCredentials();

        $key = $uname.$apiKey;

        if (hash_equals(md5($key), $token) === false)
        {
            $msg = ['token' => $token, 'computed' => md5($key)];

            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_TOKEN, $msg);

            throw new Exception\BadRequestValidationFailureException("StatusCake Token Validation Failure");
        }

        $this->validateInput($input);
    }

    protected function validateInput(array $input)
    {
        $issuer = $input['Tags'];

        if ((empty($issuer) === true) or (IFSC::exists(strtoupper($issuer)) === false))
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_ISSUER, $input);

            throw new Exception\BadRequestValidationFailureException('StatusCake Invalid Issuer from StatusCake:' . $issuer);
        }
    }

    protected function formatInput(array $input, int $status)
    {
        $formatted = [
            Entity::SOURCE      => ReasonCode::SOURCE_STATUSCAKE,
            Entity::REASON_CODE => ReasonCode::ISSUER_DOWN,
            Entity::METHOD      => Method::NETBANKING,
            Entity::PARTIAL     => false,
        ];

        if ($status === 1)
        {
            $formatted[Entity::TO] = time();
        }
        else
        {
            $formatted[Entity::FROM] = time();
        }

        $issuer = strtoupper($input['Tags']);
        
        $formatted[Entity::ISSUER] = $issuer;

        $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

        if (empty($gateways) === true)
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_GW_UNAVAILABLE, $input);
            
            throw new Exception\BadRequestValidationFailureException('StatusCake Gateway Unavailable for issuer', $issuer, $input);
        }

        // gateway here is just for reference. What we care about is actually the bank
        $formatted[Entity::GATEWAY] = $gateways[0];

        $formatted[Entity::COMMENT] = "STATUSCAKE STATUSCODE : ". $input['StatusCode'];

        return $formatted;
    }
}