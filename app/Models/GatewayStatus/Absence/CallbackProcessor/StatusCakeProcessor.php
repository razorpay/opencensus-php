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

    public function __construct()
    {
        parent::__construct();

        $this->processor = new Processor();
    }

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

            $scStatus = strtoupper($input['Status']);
            
            $status = null;

            if (in_array($scStatus, [self::STATUS_UP, self::STATUS_DOWN]) === false)
            {
                $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_STATUS,
                                    ['input' => $input]);

                throw new Exception\BadRequestValidationFailureException('Invalid Status : '. $scStatus);

            }

            $status = ($scStatus === self::STATUS_UP);

            $data = $this->formatInput($input, $status);

            if ($status === true)
            {
                $this->trace->info(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_EDIT, ['data' => $data]);

                $absent = $this->processor->fetchMostRecentActive($data);

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
                $this->trace->info(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_CREATE, ['data' => $data]);
                
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
        if (isset($input['Token']) === false)
        {
            $this->trace->critical(TraceCode::GATEWAY_ABSENCE_STATUSCODE_MISSING_TOKEN,
                                    ['data' => $input]);

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

            throw new Exception\BadRequestValidationFailureException('StatusCake Token Validation Failure');
        }

        //$this->validateInput($input);
    }

    protected function validateInput(array $input)
    {
        $issuer = $this->getIssuer($input);

        if (empty($issuer) === true)
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_ISSUER, $input);

            throw new Exception\BadRequestValidationFailureException('StatusCake Invalid Issuer from StatusCake:' , $issuer, $input);
        }
    }

    protected function getNetbankingData($issuer, $input)
    {
        if ((empty($issuer) !== true) and (IFSC::exists(strtoupper($issuer)) === true))
        {
            $issuer = strtoupper($issuer);

            $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

            if (empty($gateways) === true)
            {
                $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_GW_UNAVAILABLE, ['data' => $input]);

                throw new Exception\BadRequestValidationFailureException('StatusCake Gateway Unavailable for issuer', $issuer, $input);
            }

            // gateway here is just for reference. What we care about is actually the bank. Gateway is a required
            // entity and hence required
            $gateway = $gateways[0];

            return [$gateway, $issuer];
        }
        else
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_ISSUER, $input);

            throw new Exception\BadRequestValidationFailureException('StatusCake Invalid Issuer from StatusCake:' . $issuer);
        }
    }

    /*
     * Notes:
     * A Sample Map for gathering issuer information.
     * The Statuscake tag right now acts for issuer. The tag shall
     * be of the following format: <method>_<type>. In case of netbanking
     * the type shall be the issuer. In case of card or wallet, the type
     * shall be the gateway.
     * Gateway|Method|Network|Issuer
     * NA|Netbanking|null|Bank
     * HDFC|Card|null|HDFC
     * OlaMoney|Wallet|null|OlaMoney
     */

    protected function setIssuerMetaData(array $input, array &$formatted)
    {
        $tags = $input['Tags'];

        if (strpos($tags, '_') === false)
        {
            throw new Exception\BadRequestValidationFailureException('StatusCake invalid Tag Value', $tags, $input);
        }

        list($method, $issuer) = explode('_', $tags);

        $method = strtolower($method);

        $formatted[Entity::METHOD] = $method;

        switch ($method)
        {
            case Method::NETBANKING:

                list($gateway, $issuer) = $this->getNetbankingData($issuer, $input);

                $formatted[Entity::GATEWAY] = $gateway;

                $formatted[Entity::ISSUER] = $issuer;

                break;

            case Method::CARD:

                $formatted[Entity::GATEWAY] = $issuer;

                // issuer here does not make any sense. So, remove if it exists.
                unset($formatted[Entity::ISSUER]);

                break;

            case Method::WALLET:

                // wallets begin he gateway name with WALLET_. So, check if the gateway name actually
                // contains WALLET_. Else, append it here so validation can succeed.

                $issuer = strtoupper($issuer);

                $gateway = (strpos($issuer, 'WALLET_') === 0) ? $issuer : 'WALLET_'.$issuer;

                $formatted[Entity::GATEWAY] = $gateway;

                unset($formatted[Entity::ISSUER]);

                break;

            default:

                $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_DATA, ['data' => $input]);

                throw new Exception\BadRequestValidationFailureException('StatusCake invalid data', $method, $input);
        }
    }

    protected function formatInput(array $input, int $status)
    {
        $formatted = [
            Entity::SOURCE      => ReasonCode::SOURCE_STATUSCAKE,
            Entity::REASON_CODE => ReasonCode::ISSUER_DOWN,
            Entity::PARTIAL     => false,
        ];

        if ($status === 1)
        {
            $formatted[Entity::TO] = time();
        }
        else
        {
            $formatted[Entity::FROM] = time();

            $formatted[Entity::TO] = null;
        }

        try
        {
            $this->setIssuerMetaData($input, $formatted);
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_PARSE_ERROR, ['input' => $input]);

            throw $e;
        }

        $formatted[Entity::COMMENT] = 'STATUSCAKE STATUSCODE : '. $input['StatusCode'];

        return $formatted;
    }
}