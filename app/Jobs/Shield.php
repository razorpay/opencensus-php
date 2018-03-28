<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Risk;
use RZP\Trace\TraceCode;

/**
 * Represents asynchronous job to send PAYMENT_CREATED event to Shield
 */
class Shield extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    protected $paymentId;

    protected $repo;

    protected $shield;

    public function __construct(string $mode, string $paymentId)
    {
        parent::__construct($mode);

        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->shield = $app['shield'];

        $this->paymentId = $paymentId;

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->shield = $this->app['shield'];
    }

    public function handle()
    {
        parent::handle();

        $riskCore = new Risk\Core();

        try
        {
            $this->trace->info(TraceCode::SHIELD_JOB_RECEIVED, ['payment_id' => $this->paymentId]);

            $payment = $this->repo->payment->findOrFail($this->paymentId);

            $response = $this->shield->runFraudCheck($payment);

            if (isset($response['action']) === false)
            {
                // Already catching the exception as SHIELD_INTEGRATION_ERROR
                return;
            }

            $riskData = [];
            if ($response['action'] === 'block')
            {
                $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::CONFIRMED;
                $riskData[Risk\Entity::REASON] = Risk\RiskCode::PAYMENT_BLOCKED_BY_SHIELD;
            }
            else if ($response['action'] === 'review')
            {
                $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::SUSPECTED;
                $riskData[Risk\Entity::REASON] = Risk\RiskCode::PAYMENT_FLAGGED_BY_SHIELD;
            }

            $riskEntity = $riskCore->logPaymentForSource(
                $payment, Risk\Source::SHIELD, $riskData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SHIELD_JOB_ERROR,
                [
                    'payment_id' => $this->paymentId
                ]);
        }
    }
}
