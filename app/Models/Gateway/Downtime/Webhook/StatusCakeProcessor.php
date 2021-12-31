<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Gateway\Downtime\InputFormatter;

class StatusCakeProcessor implements ProcessorInterface
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

        $this->repo = $this->app['repo'];

        $this->core = new Downtime\Core;
    }

    protected function fetchStatusCakeCredentials()
    {
        $uname = $this->app['config']->get('applications.gateway_downtime.statuscake.username');

        $apiKey = $this->app['config']->get('applications.gateway_downtime.statuscake.api_key');

        return [$uname, $apiKey];
    }

    public function process(array $input)
    {
        try
        {
            $this->validateStatus($input['Status']);

            $status = (strtoupper($input['Status']) === self::STATUS_UP);

            $data = $this->formatInput($input, $status);

            if ($status === true)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_EDIT, ['data' => $data]);

                $downtime = $this->core->fetchMostRecentActive($data);

                if ($downtime !== null)
                {
                    $downtime->setEnd();

                    $this->repo->saveOrFail($downtime);

                    return $downtime->toArrayAdmin();
                }

                return [];
            }

            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_CREATE, ['data' => $data]);

            // this is a down, create a new entry. Unlikely that status cake might send duplicate down
            // events for the same url.
            $downtime = $this->core->create($data);

            return $downtime->toArrayAdmin();
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    protected function getNetbankingGateway(string $issuer)
    {
        if ((empty($issuer) !== true) and
            (IFSC::exists(strtoupper($issuer)) === true))
        {
            $issuer = strtoupper($issuer);

            $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

            if (empty($gateways) === true)
            {
                // $this->trace->warning(TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_GW_UNAVAILABLE, ['data' => $input]);

                throw new Exception\BadRequestValidationFailureException(
                    'StatusCake Gateway Unavailable for issuer',
                    $issuer
                );
            }

            // gateway here is just for reference. What we care about is actually the bank. Gateway is a required
            // entity and hence required
            $gateway = $gateways[0];

            return $gateway;
        }

        $this->trace->warning(TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_INVALID_ISSUER, ['issuer' => $issuer]);

        throw new Exception\BadRequestValidationFailureException(
            'StatusCake Invalid Issuer from StatusCake:' . $issuer);
    }

    protected function formatInput(array $input, bool $status)
    {
        $formatted = [
            Entity::SOURCE      => Source::STATUSCAKE,
            Entity::REASON_CODE => ReasonCode::ISSUER_DOWN,
            Entity::PARTIAL     => false,
        ];

        if ($status === true)
        {
            $formatted[Entity::END] = time();
        }
        else
        {
            $formatted[Entity::BEGIN] = time();
        }

        $this->setIssuerMetaData($input, $formatted);

        $formatted[Entity::COMMENT] = 'STATUSCAKE STATUSCODE : '. $input['StatusCode'];

        return $formatted;
    }

    /**
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
        $rawTags = $input['Tags'];

        $tags = $this->decodeJson($rawTags);

        $options = [
            Entity::METHOD    => $tags[Entity::METHOD] ?? null,
            Entity::GATEWAY   => $tags[Entity::GATEWAY] ?? Entity::ALL,
            Entity::ISSUER    => $tags[Entity::ISSUER] ?? null,
            Entity::NETWORK   => $tags[Entity::NETWORK] ?? null,
            Entity::CARD_TYPE => $tags[Entity::CARD_TYPE] ?? null,
        ];

        // this check has to happen here since
        // GATEWAY=> ALL needs to be in upper case
        if ($options[Entity::GATEWAY] === strtolower(Entity::ALL))
        {
            $options[Entity::GATEWAY] = Entity::ALL;
        }

        if (empty($options['issuer']) === false)
        {
            $options['issuer'] = strtoupper($options['issuer']);
        }

        $formatted = array_merge($formatted, $options);
    }

    protected function validateStatus($status)
    {
        $validStatus = [self::STATUS_UP, self::STATUS_DOWN];

        if (in_array(strtoupper($status), $validStatus, true) === false)
        {
            $this->trace->warning(
                TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_INVALID_STATUS,
                ['status' => $status]);

            throw new Exception\BadRequestValidationFailureException(
                'Invalid StatusCake status provided: ' . $status);
        }
    }

    public function validate(array $input)
    {
        if (isset($input['Token']) === false)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_STATUSCODE_MISSING_TOKEN,
                ['input' => $input]);

            throw new Exception\BadRequestValidationFailureException(
                'StatusCake token missing');
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
            $this->trace->warning(
                TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_INVALID_TOKEN, []);

            throw new Exception\BadRequestValidationFailureException(
                'StatusCake token validation failure.');
        }
    }

    protected function decodeJson($json)
    {
        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                $this->trace->error(
                    TraceCode::GATEWAY_DOWNTIME_STATUSCAKE_INVALID_TAGS,
                    [
                        'json' => $json,
                        'error' => json_last_error()
                    ]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }
}
