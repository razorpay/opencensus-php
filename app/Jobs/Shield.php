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

        try
        {
            $this->trace->info(TraceCode::SHIELD_JOB_RECEIVED, ['payment_id' => $this->paymentId]);

            $app = App::getFacadeRoot();

            $repo = $app['repo'];

            $payment = $repo->payment->findOrFail($this->paymentId);

            $shield = $app['shield'];

            $response = $shield->runFraudCheck($payment);

            $riskCore = new Risk\Core();

            $riskCore->create($payment, [
                Risk\Entity::FRAUD_TYPE => Risk\Type::SUSPECTED,
                Risk\Entity::SOURCE     => Risk\Source::SHIELD,
                Risk\Entity::REASON     => 'Blocked by shield',
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SHIELD_JOB_ERROR,
                [
                    'data' => $this->paymentCreatedEvent
                ]);
        }
    }
}
