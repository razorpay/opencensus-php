<?php

namespace RZP\Models\Gateway\Downtime;

use Illuminate\Support\Facades\Redis;
use RZP\Models\Admin\Query\Validator;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Services\DowntimeSlackNotification;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime\Webhook;
use RZP\Jobs\DynamicNetBankingUrlUpdater;

class Service extends Base\Service
{
    protected $processor;

    public function create(array $input)
    {
        $downtime = $this->core()->create($input);

        return $downtime->toArrayAdmin();
    }

    public function edit($id, array $input)
    {
        $downtime = $this->core()->edit($id, $input);

        return $downtime->toArrayAdmin();
    }

    public function delete(string $id)
    {
        $downtime = $this->repo->gateway_downtime->findOrFailPublic($id);

        $downtime = $this->core()->delete($downtime);

        return $downtime->toArrayAdmin();
    }

    public function getExternalApiHealth($input)
    {
        return $this->core()->getExternalApiHealthData($input);
    }

    public function getGatewayDowntimeDataForDashboard(): array
    {
        $downtimes = $this->core()->getCurrentAndFutureGatewayDowntimeData();

        return $downtimes->toArrayAdmin();
    }

    public function getGatewayDowntimeDataForPayment($input)
    {
        $this->trace->info(TraceCode::GET_GATEWAY_DOWNTIME_REQUEST, $input);

        $gatewayDowntimes = $this->repo->useSlave(function () use ($input) {
            return $this->core()->getApplicableDowntimesForPaymentForRouter($input['terminals'], $input['payment']);
        });

        return [
            'gateway_downtimes' => $gatewayDowntimes,
        ];
    }

    public function archiveGatewayDowntimes(): array
    {
        return $this->core()->archiveGatewayDowntimes();
    }

    public function getPublicGatewayDowntimeData(): array
    {
        $downtimes = $this->core()->getPublicGatewayDowntimeData();

        return $downtimes->toArrayCheckout();
    }

    public function getDowntimeDataForMerchant(): array
    {
        // Currently we are only exposing netbanking downtimes over the public
        // downtime fetch route. For other methods, support will be added after
        // the relevant downtimes are being utilised on Razorpay checkout.
        $downtimes = $this->core()->getPublicGatewayDowntimeData([
            Method::NETBANKING,
        ]);

        return $downtimes->toArrayPublic();
    }

    public function processGatewayDowntimeWebhook(string $source, array $input)
    {
        $this->setMode();

        if (strtoupper($source) === Source::VAJRA) {
            throw new Exception\BadRequestValidationFailureException(
                $source . ' downtime is not created through this webhook.');
        }

        $processor = new Webhook\Processor($source);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, $input);

        $processor->validate($input);

        $data = $processor->process($input);

        return $data;
    }

    public function processGatewayDowntimeVajraWebhook(array $input)
    {
        $this->setMode();

        $processor = new Webhook\Processor(Source::VAJRA);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, $input);

        $processor->validate($input);

        $data = $processor->process($input);

        return $data;
    }

    public function processDowntimeServiceWebhook(array $input)
    {
        $this->setMode();

        $processor = new Webhook\Processor(Source::DOWNTIME_SERVICE);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, $input);

        $processor->validate($input);

        $data = $processor->process($input);

        $this->notifyOnSlack($input);

        return $data;
    }

    public function updateDowntimeSlackNotifationMerchantNames(array $input)
    {
        $this->setMode();

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, $input);

        $redis = Redis::Connection('mutex_redis');

        foreach ($input as $mMap) {
            $key = "{downtime}:merchant_name_".$mMap['id'];
            $redis->HMSET($key, ["name"=>$mMap['name']]);
        }
    }

    public function setMode()
    {
        $mode = $this->core()::getMode();

        $this->auth->setModeAndDbConnection($mode);
    }


    public function purgeKeys()
    {
        (new GatewayDowntimeDetection())->purgeKeys();
    }

    public function createDowntimeIfNecessary(array $input)
    {
        (new DowntimeDetection())->createDowntimeDetectionJobs();
    }

    public function phonePeDowntime($input)
    {
        $this->setMode();

        $processor = new Webhook\Processor(Source::PHONEPE);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, []);

        //$input = [];

        $data = $processor->process($input);

        return $data;
    }

    public function createFpxDowntimes($input)
    {
        $this->setMode();

        $processor = new Webhook\Processor(Source::PAYNET);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, []);

        $processor->validate($input);

        $data = $processor->process($input);

        return $data;
    }

    private function notifyOnSlack(array $input): void
    {
        try
        {
            $this->app['downtimeSlackNotification']->notifyPaymentDowntime($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::FAILED_DOWNTIME_SLACK_NOTIFICATION, ["downtime" => $input]);
        }
    }
}
