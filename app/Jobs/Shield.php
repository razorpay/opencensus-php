<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Risk;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

/**
 * Represents asynchronous job to send PAYMENT_CREATED event to Shield
 */
class Shield extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Associative array with key as batch type and value
     * as Batch entity object.
     *
     * @var array
     */
    protected $paymentCreatedEvent;

    public function __construct(string $mode, array $paymentCreatedEvent)
    {
        parent::__construct($mode);

        $this->paymentCreatedEvent = $paymentCreatedEvent;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::SHIELD_JOB_RECEIVED, $this->paymentCreatedEvent);

            $app = App::getFacadeRoot();

            $shield = $app['shield'];

            $repo = $app['repo'];

            $response = $shield->evaluateRules($this->paymentCreatedEvent);

            $paymentId = $this->paymentCreatedEvent['properties']['payment_id'];

            Payment\Entity::verifyIdAndStripSign($paymentId);

            $payment = $repo->payment->findOrFail($paymentId);

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
