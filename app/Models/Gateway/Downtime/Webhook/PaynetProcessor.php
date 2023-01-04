<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants as constants;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\Webhook\Validator\Validator;
use RZP\Models\Gateway\Downtime as GatewayDowntime;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Payment\Method;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;

class PaynetProcessor implements ProcessorInterface
{
    protected $app;

    protected $trace;

    protected $core;

    protected $repo;

    protected $env;

    protected $mode;

    /*
     Retail => This mode is used for B2C transactions, where Seller is the
     business organization, and Buyer is the individual.
     Corporate => This mode is used for B2B transactions, e-commerce system, where
     business organization acting as both Buyers and Sellers
    */
    protected $transactionModes = [
            GatewayDowntime\Constants::RETAIL,
            GatewayDowntime\Constants::CORPORATE
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->env = $this->app['env'];

        $this->mode = $this->app['rzp.mode'];

        $this->core = new GatewayDowntime\Core;
    }

    public function validate(array $input)
    {
        (new Validator())->validateInput(Validator::FPX_CRON, $input);
    }

    public function process(array $input)
    {
        $input["terminal"] = $this->repo->terminal->findOrFail($input["terminal_id"]);

        $responses = [];

        // We are hitting external api(Paynet) for both the transaction modes separately
        // Because different banks/issuers might be down for different transaction modes
        foreach ($this->transactionModes as $transactionMode)
        {
            $input[GatewayDowntime\Constants::TRANSACTION_TYPE] = $transactionMode;

            $downtimeData = $this->app[constants\Entity::MOZART]->getDowntimeIssuerData($input, Method::FPX);

            if (empty($downtimeData["data"]["bankList"]) === true)
            {
                throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_INVALID_DATA);
            }

            $response["status"] = $this->updateDowntimeFpxData($downtimeData["data"], $transactionMode);

            array_push($responses, $response);
        }

        $this->trace->info(TraceCode::FPX_DOWNTIME_CREATE, [$responses]);

        return $responses;
    }

    public function fetchActiveDowntimeForFpx()
    {
        $input = $this->baseInput();

        return $this->core->fetchActiveDowntime($input);
    }

    public function getTransactionModeBankList($transactionMode)
    {
        if ($transactionMode === GatewayDowntime\Constants::RETAIL)
        {
            return array_flip(Payment\Processor\Fpx::$fpxB2CBankCodeMapping);
        }

        return array_flip(Payment\Processor\Fpx::$fpxB2BBankCodeMapping);

    }

    public function calculateUnavailabeBanks(Collection $bankDowntimes = null)
    {
        if ($bankDowntimes === null)
        {
            return [];
        }

        return $bankDowntimes->pluck(GatewayDowntime\Entity::ISSUER)->toArray();
    }

    public function fetchResolveAndCreateDowntimeData($transactionModeBankList, $issuerListForExistingDowntimes, $downtimeData)
    {
        $resolveDowntimeIssuers = [];

        $createDowntimeIssuers = [];

        $existingDowntimeIssuers = [];

        foreach ($transactionModeBankList as $bankCode => $issuer)
        {
            if ($downtimeData["bankList"][$bankCode] === GatewayDowntime\Constants::ACTIVE)
            {
                array_push($resolveDowntimeIssuers, $issuer);
                continue;
            }

            if (in_array($issuer, $issuerListForExistingDowntimes) === true)
            {
                array_push($existingDowntimeIssuers, $issuer);
                continue;
            }

            array_push($createDowntimeIssuers, $issuer);
        }

        return [$resolveDowntimeIssuers, $createDowntimeIssuers, $existingDowntimeIssuers];
    }

    public function createDowntime($createDowntimeIssuers)
    {
        foreach ($createDowntimeIssuers as $issuers)
        {
            $downtime = $this->baseInput($issuers, 'begin');

            $this->core->create($downtime);
        }
    }


    /*
    @param array $transactionModeBankList : List of the available banks as per the respective transaction mode
    @param array $issuerListForExistingDowntimes : List of the issuers for which downtime is already present
    @param array $downtimeData : Latest downtime data that we have recieved from the external Api
    @param Collection $activeDowntimes : Existing Active downtime data for fpx fetched from gateway_downtime db

    @return array : created and to be resolved downtime
    */
    public function createAndResolveFpxDowntime($transactionModeBankList, $issuerListForExistingDowntimes, $downtimeData, $activeDowntimes)
    {
       list($resolveDowntimeIssuers, $createDowntimeIssuers, $existingDowntimeIssuers) = $this->fetchResolveAndCreateDowntimeData($transactionModeBankList, $issuerListForExistingDowntimes, $downtimeData);

       $this->createDowntime($createDowntimeIssuers);

       $this->resolveDowntimes($resolveDowntimeIssuers, $activeDowntimes);

       return [array_merge($createDowntimeIssuers, $existingDowntimeIssuers), $resolveDowntimeIssuers];
    }

    /*
        Following steps are getting performed in this func :
        1. Get the banks list for the respective transaction mode
        2. Fetch active gateway downtimes for the Gateway as FPX and issuer as FPX
        3. Get issuer list from active downtime data
        4. Create Downtime for the issuers in below 2 situations
            a) where status of issuer is Blocked
            b) where data for the banks is not received in external downtime api response
            Downtime will be created only if issuer is not present in list created in step 4
        5. Resolve the downtimes for the issuers, where downtime is already present and their status
        is active now

        @param array $downtimeData
        @param string $transactionMode

        @return array
    */
    public function updateDowntimeFpxData($downtimeData, $transactionMode)
    {
        $transactionModeBankList = $this->getTransactionModeBankList($transactionMode);

        $activeDowntimes = $this->fetchActiveDowntimeForFpx();

        $issuerListForExistingDowntimes = $this->calculateUnavailabeBanks($activeDowntimes);

        list($createdDowntimes, $resolvedDowntimes) = $this->createAndResolveFpxDowntime($transactionModeBankList, $issuerListForExistingDowntimes, $downtimeData, $activeDowntimes);

        $response = [
            "active_downtimes" => $createdDowntimes,
            "resolve_downtimes" => $resolvedDowntimes
        ];

        return $response;
    }

    protected function resolveDowntimes($resolveDowntimeIssuers, $activeDowntimes)
    {
        $resolveDowntimes = $activeDowntimes->whereIn(Entity::ISSUER, $resolveDowntimeIssuers);

        foreach ($resolveDowntimes as $downTime)
        {
            $downTime->setEnd();

            $this->repo->saveOrFail($downTime);
        }
    }

    protected function baseInput($issuer = null, $status = null)
    {
        $baseInput = [
            Entity::GATEWAY     => Payment\Gateway::FPX,
            Entity::REASON_CODE => ReasonCode::ISSUER_DOWN,
            Entity::METHOD      => Method::FPX,
            Entity::SOURCE      => GatewayDowntime\Source::PAYNET,
            Entity::PARTIAL     => false,
            Entity::SCHEDULED   => false,
            Entity::NETWORK     => Entity::NA,
            Entity::ISSUER      => $issuer
        ];

        if($status === 'begin')
        {
            $baseInput[Entity::BEGIN] = Carbon::now()->getTimestamp();
        }
        elseif ( $status === 'end')
        {
            $baseInput[Entity::END] = Carbon::now()->getTimestamp();
        }

        return $baseInput;
    }

}
