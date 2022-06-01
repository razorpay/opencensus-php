<?php

namespace RZP\Models\Payment\Verify;

use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Card\Network;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Metric;

class CaptureVerify extends Verify
{
    public function verifyPayment(Payment\Entity $payment, string $filter = null, array $gatewayData = null)
    {
        $result = Result::SUCCESS;

        $action = null;

        $merchant = $payment->merchant;

        //
        // Exception is thrown when the there's a mismatch
        // between payment status and status returned by gateway.
        // Most cases, this would mean that the payment is in failed
        // state and gateway returned back status authorized.
        //
        try
        {
            $this->processor($merchant)->verifyNewRoute($payment, 'captured/verify');

            // Verification was successful so move this verification to last state
            $this->updateVerifyBucket($payment, $filter, self::LAST);

            (new Payment\Metric)->pushCapturedVerifyMetrics($payment, Metric::SUCCESS, []);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            (new Payment\Metric)->pushCapturedVerifyMetrics($payment, Metric::FAILED, [],$e);

            $action = $e->getAction();

            $result = Result::ERROR;

            $this->trace->info(
                TraceCode::VERIFY_ACTION,
                [
                    'action' => $action,
                ]
            );

            switch ($action)
            {
                case Action::BLOCK:
                    $this->blockGatewayForVerify($payment);
                    $this->updateVerifyBucket($payment, $filter, self::CURR);
                    break;

                case Action::RETRY:
                    $this->updateVerifyBucket($payment, $filter, self::CURR);
                    break;

                case Action::FINISH:
                    $result = Result::UNKNOWN;
                    $this->logForVerificationFailedReport($payment);
                    $this->updateVerifyBucket($payment, $filter, self::LAST);
                    break;

                default:
                    $this->logForVerificationFailedReport($payment);
                    $this->updateVerifyBucket($payment, $filter, self::NEXT);
                    break;
            }
        }
        catch (Exception\GatewayRequestException $e)
        {
            (new Payment\Metric)->pushCapturedVerifyMetrics($payment, Metric::FAILED, [],$e);

            if ($e instanceof Exception\GatewayTimeoutException)
            {
                $result = Result::TIMEOUT;

                $timeoutThreshold = $this->getTimeoutThresholdForBlock($payment);

                $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                    $timeoutThreshold,
                    self::GATEWAY_TIMEOUT_CACHE_KEY_PREFIX);

                $this->trace->traceException($e,
                                Trace::WARNING,
                                TraceCode::GATEWAY_REQUEST_TIMEOUT,
                                [
                                    'payment_id' => $payment->getId(),
                                    'gateway'    => $payment->getGateway()
                                ]);
            }
            else
            {
                $result = Result::REQUEST_ERROR;

                $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                    self::GATEWAY_REQUEST_ERROR_THRESHOLD,
                    self::GATEWAY_REQUEST_ERROR_CACHE_KEY_PREFIX);

                $this->trace->traceException($e,
                                Trace::WARNING,
                                TraceCode::GATEWAY_REQUEST_ERROR,
                                [
                                    'payment_id' => $payment->getId(),
                                    'gateway'    => $payment->getGateway()
                                ]);
            }

            $this->updateVerifyBucket($payment, $filter, self::NEXT);
        }
        catch (\Throwable $e)
        {
            $result = Result::ERROR;

            $this->updateVerifyBucket($payment, $filter, self::NEXT);

            (new Payment\Metric)->pushCapturedVerifyMetrics($payment, Metric::FAILED, [],$e);
            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.
            $extraData = ['payment_id' => $payment->getId()];

            $this->trace->traceException($e, null, null, $extraData);
        }
        finally
        {
            if($payment->getVerifyBucket() >= 4)
            {
                $this->trace->info(
                    TraceCode::CAPTURE_VERIFY_ONHOLD_ACTION,
                    [
                        'payment_id' => $payment->getId(),
                        'verified_at'   => $payment->getVerifyAt(),
                        'verify_bucket' => $payment->getVerifyBucket(),
                        'gateway'       => $payment->getGateway(),
                        'status'        => $payment->getStatus(),
                        'result'        => $result,
                        'verify_action' => $action,
                    ]
                );
            }
        }

        return $result;
    }

    protected function logForVerificationFailedReport($payment)
    {
        $gateway = $payment->getGateway();

        if (Payment\Gateway::isCaptureVerifyReportEnabledGateways($gateway) === true)
        {
            if (($gateway === Payment\Gateway::HITACHI) and ($payment->hasCard() === true)
                and ($payment->card->getNetwork() === Network::getFullName(Network::RUPAY)))
            {
                return;
            }

            $this->trace->info(
                TraceCode::CAPTURE_VERIFY_FAILED_PAYMENT,
                [
                    'payment_id'    => $payment->getId(),
                    'verified_at'   => $payment->getVerifyAt(),
                    'verify_bucket' => $payment->getVerifyBucket(),
                    'gateway'       => $payment->getGateway(),
                    'status'        => $payment->getStatus(),
                ]
            );
        }
    }
}
