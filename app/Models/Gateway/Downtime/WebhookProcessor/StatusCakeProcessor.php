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
        $uname = $this->app['config']->get('applications.gateway_downtime.statuscake.username');

        $apiKey = $this->app['config']->get('applications.gateway_downtime.statuscake.api_key');

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
                        Entity::FROM   => $absent->getDowntimeFrom(),
                        Entity::TO     => time(),
                        Entity::SOURCE => $absent->getSource()
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

    protected function getNetbankingGateway(string $issuer)
    {
        if ((empty($issuer) !== true) and (IFSC::exists(strtoupper($issuer)) === true))
        {
            $issuer = strtoupper($issuer);

            $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

            if (empty($gateways) === true)
            {
                // $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_GW_UNAVAILABLE, ['data' => $input]);

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
        else
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_ISSUER, ['issuer' => $issuer]);

            throw new Exception\BadRequestValidationFailureException(
                'StatusCake Invalid Issuer from StatusCake:' . $issuer);
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

        $fmtTags = [];

        try
        {
            $fmtTags = json_decode($tags, true);
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_TAGS,
                [
                    'tags' => $tags,
                    'input' => $input,
                    'exception' => $e->getMessage()
                ]
            );

            throw new Exception\LogicException('StatusCake invalid Tag Value', $tags, $input);
        }

        $jsonError = json_last_error();

        if ($jsonError !== 0)
        {
            $this->trace->warning(TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_TAGS,
                [
                    'tags' => $tags,
                    'input' => $input,
                    'json_error' => $jsonError
                ]
            );

            throw new Exception\LogicException('StatusCake invalid Tag Value', $tags, $input);
        }

        $formatted = [];

        $method = isset($fmtTags[Entity::METHOD]) ? strtolower($fmtTags[Entity::METHOD]) : null;

        $gateway = isset($fmtTags[Entity::GATEWAY]) ? strtolower($fmtTags[Entity::GATEWAY]) : null;

        $issuer = isset($fmtTags[Entity::ISSUER]) ? strtolower($fmtTags[Entity::ISSUER]) : null;

        $network = isset($fmtTags[Entity::NETWORK]) ? strtolower($fmtTags[Entity::NETWORK]) : null;

        $cardType = isset($fmtTags[Entity::CARD_TYPE]) ? strtolower($fmtTags[Entity::CARD_TYPE]) : null;

        $method = strtolower($method);

        $formatted[Entity::METHOD] = $method;

        switch ($method)
        {
            case Method::NETBANKING:

                if (isset($issuer) === false)
                {
                    $this->trace->warning(
                        TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_NBDATA,
                        ['data' => $fmtTags]
                    );

                    throw new Exception\BadRequestValidationFailureException(
                        'StatusCake invalid Netbanking data',
                        $method,
                        $fmtTags
                    );
                }

                if (isset($gateway) === false)
                {
                    $gateway = $this->getNetbankingGateway($issuer);
                }

                $formatted[Entity::GATEWAY] = $gateway;

                $formatted[Entity::ISSUER] = $issuer;

                break;

            case Method::CARD:

                if (isset($gateway) === false)
                {
                    $this->trace->warning(
                        TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_CDATA,
                        ['data' => $fmtTags]
                    );

                    throw new Exception\BadRequestValidationFailureException(
                        'StatusCake invalid Card data',
                        $method,
                        $fmtTags
                    );
                }

                $formatted[Entity::GATEWAY] = $gateway;

                if (isset($issuer) === true)
                {
                    $formatted[Entity::ISSUER] = $issuer;
                }

                if (isset($network) === true)
                {
                    $formatted[Entity::NETWORK] = $network;
                }

                if (isset($cardType) === true)
                {
                    $formatted[Entity::CARD_TYPE] = $cardType;
                }

                break;

            case Method::WALLET:

                // wallets begin he gateway name with WALLET_. So, check if the gateway name actually
                // contains WALLET_. Else, append it here so validation can succeed.

                if (isset($gateway) === false)
                {
                    $this->trace->warning(
                        TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_WDATA,
                        ['data' => $fmtTags]
                    );

                    throw new Exception\BadRequestValidationFailureException(
                        'StatusCake invalid Wallet data',
                        $method,
                        $fmtTags
                    );
                }

                $formatted[Entity::GATEWAY] = $gateway;

                break;

            default:

                $this->trace->warning(
                    TraceCode::GATEWAY_ABSENCE_STATUSCAKE_INVALID_DATA,
                    ['data' => $input]
                );

                throw new Exception\BadRequestValidationFailureException(
                    'StatusCake invalid data',
                    $method,
                    $input
                );
        }

        // $formatted = InputFormatter::format($formatted);
    }

    protected function formatInput(array $input, int $status)
    {
        $formatted = [
            Entity::SOURCE      => Source::STATUSCAKE,
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
