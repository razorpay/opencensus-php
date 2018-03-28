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

    public function __construct(string $mode, string $paymentId)
    {
        parent::__construct($mode);

        $this->paymentId = $paymentId;
    }

    public function handle()
    {
        parent::handle();

        $riskCore = new Risk\Core();

        $app = App::getFacadeRoot();

        $shield = $app['shield'];

        try
        {
            $this->trace->info(TraceCode::SHIELD_JOB_RECEIVED, ['payment_id' => $this->paymentId]);

            $payment = $this->repoManager->payment->findOrFail($this->paymentId);

            $response = $shield->runFraudCheck($payment);

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
