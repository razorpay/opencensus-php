<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Risk;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Services\ShieldClient;
use RZP\Exception\LogicException;

/**
 * Represents asynchronous job to send PAYMENT_CREATED event to Shield
 */
class RunShieldCheck extends Job
{
    /**
     * @var string
     */
    protected $payment;

    const ACTION_REVIEW = 'review';
    const ACTION_BLOCK  = 'block';
    const ACTION_ALLOW  = 'allow';

    public function __construct(string $mode, Payment\Entity $payment)
    {
        parent::__construct($mode);

        $this->payment = $payment;
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
            $this->trace->info(TraceCode::SHIELD_JOB_RECEIVED, ['payment_id' => $this->payment->getId()]);

            $response = $shield->runFraudCheck($this->payment);

            if (isset($response['action']) === false)
            {
                // Already catching the exception as SHIELD_INTEGRATION_ERROR
                return;
            }

            $riskData = [];

            $action = $response['action'];

            $fraudType = null;
            $reason = null;

            switch ($action)
            {
                case self::ACTION_BLOCK:
                    $fraudType = Risk\Type::CONFIRMED;
                    $reason    = Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD;
                    break;

                case self::ACTION_REVIEW:
                    $fraudType = Risk\Type::SUSPECTED;
                    $reason    = Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_SHEILD;
                    break;

                case self::ACTION_ALLOW:
                    return;

                default:
                    throw new LogicException('Unexpected shield action: ' . $action);
            }

            $riskData[Risk\Entity::FRAUD_TYPE] = $fraudType;
            $riskData[Risk\Entity::REASON]     = $reason;

            $riskCore->logPaymentForSource($this->payment, Risk\Source::SHIELD, $riskData);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SHIELD_JOB_ERROR,
                [
                    'payment_id' => $this->payment->getId()
                ]);
        }
    }
}
