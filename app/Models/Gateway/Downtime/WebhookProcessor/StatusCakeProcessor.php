<?php

namespace RZP\Models\Gateway\Downtime\WebhookProcessor;

use App;
use RZP\Exception;
use RZP\Models\Gateway\Downtime\InputFormatter;
use RZP\Models\Payment\Gateway;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Gateway\Downtime\Source;

class StatusCakeProcessor implements AbstractProcessorInterface
{
    const STATUS_UP = 'UP';

    const STATUS_DOWN = 'DOWN';

    protected $app;

    protected $trace;

    protected $core;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->core = new Downtime\Core();
    }

    protected function fetchStatusCakeCredentials()
    {
        $uname = $this->app['config']->get('applications.gateway_absence.statuscake.username');

        $apiKey = $this->app['config']->get('applications.gateway_absence.statuscake.api_key');

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

                $absent = $this->core->fetchMostRecentActive($data);

                if (empty($absent) === false)
                {
                    $editData = [
                        Entity::DOWNTIME_FROM   => $absent->getDowntimeFrom(),
                        Entity::DOWNTIME_TO     => time(),
                        Entity::SOURCE          => $absent->getSource()
                    ];

                    $downWindow = $this->core->edit($absent, $editData);

                    return $downWindow->toArrayPublic();
                }

                return [];
            }
            else
            {
                $this->trace->info(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_CREATE, ['data' => $data]);

                // this is a down, create a new entry. Unlikely that status cake might send duplicate down
                // events for the same url.
                $downWindow = $this->core->create($data);

                return $downWindow;
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
    }

    protected function getNetbankingData(string $issuer, array $input)
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

                break;

            case Method::WALLET:

                // wallets begin he gateway name with WALLET_. So, check if the gateway name actually
                // contains WALLET_. Else, append it here so validation can succeed.

                $issuer = strtoupper($issuer);

                $gateway = (strpos($issuer, 'WALLET_') === 0) ? $issuer : 'WALLET_'.$issuer;

                $formatted[Entity::GATEWAY] = $gateway;

                break;

            default:

                $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_DATA, ['data' => $input]);

                throw new Exception\BadRequestValidationFailureException('StatusCake invalid data', $method, $input);
        }

        $formatted = InputFormatter::format($formatted);
    }

    protected function formatInput(array $input, int $status)
    {
        $formatted = [
            Entity::SOURCE      => Source::SOURCE_STATUSCAKE,
            Entity::REASON_CODE => ReasonCode::ISSUER_DOWN,
            Entity::PARTIAL     => false,
        ];

        if ($status === 1)
        {
            $formatted[Entity::DOWNTIME_TO] = time();
        }
        else
        {
            $formatted[Entity::DOWNTIME_FROM] = time();

            $formatted[Entity::DOWNTIME_TO] = null;
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
