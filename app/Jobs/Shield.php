<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Risk;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Services\ShieldClient;
use RZP\Exception\LogicException;

/**
 * Represents asynchronous job to send PAYMENT_CREATED event to Shield
 */
class Shield extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var string
     */
    protected $paymentId;

    const ACTION_REVIEW = 'review';
    const ACTION_BLOCK  = 'block';

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

        /** @var ShieldClient $shield */
        $shield = $app['shield'];

        try
        {
            $this->trace->info(TraceCode::SHIELD_JOB_RECEIVED, ['payment_id' => $this->paymentId]);

            /** @var Payment\Entity $payment */
            $payment = $this->repoManager->payment->findOrFail($this->paymentId);

            $response = $shield->runFraudCheck($payment);

            if (isset($response['action']) === false)
            {
                // Already catching the exception as SHIELD_INTEGRATION_ERROR
                return;
            }

            $riskData = [];
            $action   = $response['action'];

            $fraudType = $reason = null;

            switch ($action)
            {
                case self::ACTION_BLOCK:
                    $fraudType = Risk\Type::CONFIRMED;
                    $reason    = Risk\RiskCode::PAYMENT_BLOCKED_BY_SHIELD;
                    break;

                case self::ACTION_REVIEW:
                    $fraudType = Risk\Type::SUSPECTED;
                    $reason    = Risk\RiskCode::PAYMENT_FLAGGED_BY_SHIELD;
                    break;

                default:
                    throw new LogicException('Unexpected shield action: ' . $action);
            }

            $riskData[Risk\Entity::FRAUD_TYPE] = $fraudType;
            $riskData[Risk\Entity::REASON]     = $reason;

            $riskCore->logPaymentForSource($payment, Risk\Source::SHIELD, $riskData);

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
