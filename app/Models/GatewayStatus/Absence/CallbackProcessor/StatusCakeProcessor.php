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
use RZP\Error\ErrorCode;

class StatusCakeProcessor extends AbstractProcessor
{

    protected $app;

    protected $trace;

    protected $repo;

    protected $sCakeUsername;
    
    protected $sCakeApiKey;

    protected $processor;

    const STATUS_UP = 'UP';

    const STATUS_DOWN = 'DOWN';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
        
        $this->repo = $this->app['repo'];

        $this->sCakeUsername = $this->app['config']->get('gateway.absence.statuscake.username');

        $this->sCakeApiKey = $this->app['config']->get('gateway.absence.statuscake.api_key');

        $this->processor = new Processor();
    }

    public function process(array $input)
    {
        try
        {
            $this->validateRequest($input);
        }
        catch(\Exception $e)
        {
            throw $e;
        }
        
        try 
        {
            list($data, $status) = $this->formatInput($input);

            if ($status === 1)
            {
                // this is a flip from down to up, hence fetch the entry that is down and update accordingly
                // fetch the issuer who is currently down(without to time in case of an unscheduled downtime)
                $params = [
                    Entity::GATEWAY => $data[Entity::GATEWAY],
                    Entity::ISSUER  => $data[Entity::ISSUER],
                    Entity::METHOD  => $data[Entity::METHOD],
                    Entity::SOURCE  => $data[Entity::SOURCE]
                ];

                $absent = $this->processor->verifyIfExists($data);

                if (empty($absent) === false)
                {
                    $editData = [
                        Entity::FROM => $absent->getFrom(),
                        Entity::TO => time()
                    ];

                    return $this->processor->editAction($absent->id, $editData);
                }
            }
            else
            {
                // this is a down, create a new entry. Unlikely that status cake might send duplicate down
                // events for the same url. Hence we do not need to apply any de-duplication here
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

            throw new Exception\BadRequestValidationFailureException("StatusCake Token Missing");
        }

        return $this->validateToken($input);
    }

    protected function validateToken(array $input)
    {
        $token = $input['Token'];

        $key = $this->sCakeUsername.$this->sCakeApiKey;

        if (strcmp(md5($key),$token) != 0)
        {
            $msg = ['token' => $token, 'computed' => md5($key)];

            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_TOKEN, $msg);

            throw new Exception\BadRequestValidationFailureException("StatusCake Token Validation Failure");
        }

        return $this->validateInput($input);
    }

    protected function validateInput(array $input)
    {
        $issuer = $input['Tags'];

        if ((empty($issuer) === true) or ((IFSC::exists(strtoupper($issuer)) === false)))
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_ISSUER, $input);

            throw new Exception\BadRequestValidationFailureException('StatusCake Invalid Issuer from StatusCake:' . $issuer);
        }
    }

    protected function formatInput(array $input)
    {
        $formatted = [
            Entity::SOURCE      => ReasonCode::SOURCE_STATUSCAKE,
            Entity::REASON_CODE => ReasonCode::ISSUER_DOWN,
            Entity::METHOD      => Method::NETBANKING,
            Entity::PARTIAL     => false,
        ];

        $sStatus = strtoupper($input['Status']);

        $status = ($sStatus === self::STATUS_UP) ? 1 : 0;

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
            
            throw new Exception\BadRequestValidationFailureException('StatusCake Gateway Unavailable for issuer' . $issuer);
        }

        // gateway here is just for reference. What we care about is actually the bank
        $formatted[Entity::GATEWAY] = $gateways[0];

        $formatted[Entity::COMMENT] = "STATUSCAKE STATUSCODE : ". $input['StatusCode'];

        return [$formatted, $status];
    }
}