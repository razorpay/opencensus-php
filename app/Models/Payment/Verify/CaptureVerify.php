<?php

namespace RZP\Models\Payment\Verify;

use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class CaptureVerify extends Verify
{
    public function verifyPayment(Payment\Entity $payment, string $filter = null)
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
            $this->processor($merchant)->verify($payment);

            // Verification was successful so move this verification to last state
            $this->updateVerifyBucket($payment, $filter, self::LAST);
        }
        catch (Exception\PaymentVerificationException $e)
        {
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
                    $this->blockGatewayForVerify($payment->getGateway());
                    $this->updateVerifyBucket($payment, $filter, self::CURR);
                    break;

                case Action::RETRY:
                    $this->updateVerifyBucket($payment, $filter, self::CURR);
                    break;

                case Action::FINISH:
                    $result = Result::UNKNOWN;
                    $this->handleFinishAction($payment);
                    $this->updateVerifyBucket($payment, $filter, self::LAST);
                    break;

                default:
                    $this->updateVerifyBucket($payment, $filter, self::NEXT);
                    break;
            }
        }
        catch (Exception\GatewayRequestException $e)
        {
            if ($e instanceof Exception\GatewayTimeoutException)
            {
                $result = Result::TIMEOUT;

                $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                    self::GATEWAY_TIMEOUT_THRESHOLD,
                    self::GATEWAY_TIMEOUT_CACHE_KEY_PREFIX);

                $this->trace->info(TraceCode::GATEWAY_REQUEST_TIMEOUT,
                                   ['payment_id' => $payment->getId()]);
            }
            else
            {
                $result = Result::REQUEST_ERROR;

                $this->checkForPreviousRequestErrorAndBlockGatewayIfApplicable($payment,
                    self::GATEWAY_REQUEST_ERROR_THRESHOLD,
                    self::GATEWAY_REQUEST_ERROR_CACHE_KEY_PREFIX);

                $this->trace->info(TraceCode::GATEWAY_REQUEST_ERROR,
                                   ['payment_id' => $payment->getId()]);
            }

            $this->updateVerifyBucket($payment, $filter, self::NEXT);
        }
        catch (\Throwable $e)
        {
            $result = Result::ERROR;

            $this->updateVerifyBucket($payment, $filter, self::NEXT);

            // @note: If payment verification fails due to any reason
            // other than expected ones, we should log it as an error
            // exception.
            $extraData = ['payment_id' => $payment->getId()];

            $this->trace->traceException($e, null, null, $extraData);
        }
        finally
        {
            // Raise an alert if current bucket is greater than equal to 4
            // and error result is error or unknown.
            // Otherwise raise an alert if verify bucket is last bucket
            if ((($payment->getVerifyBucket() >= 4) and
                (($result === Result::ERROR) or
                 ($result === Result::UNKNOWN))) or
                (($payment->getVerifyBucket() == 9) and
                 ($result !== Result::SUCCESS)))
            {
                // Put the settlement on hold if verification has failed
                // $payment->setOnHold(true);

                // $this->repo->saveOrFail($payment);


                // Raise an alert on slack for failed captured payment verification
                $message = 'Captured payment verification failed';

                $slackArray = [
                    'payment_id'    => $payment->getId(),
                    'verified_at'   => $payment->getVerifyAt(),
                    'verify_bucket' => $payment->getVerifyBucket(),
                    'gateway'       => $payment->getGateway(),
                    'status'        => $payment->getStatus(),
                    'result'        => $result,
                    'verify_action' => $action,
                ];

                $this->slack->queue(
                    $message,
                    $slackArray,
                    [
                        'channel' => $this->slackChannel,
                    ]
                );
            }
        }

        return $result;
    }

    protected function handleFinishAction($payment)
    {
        // Dry run temporary alert on slack for failed captured payment verification
        $message = 'Dry Run - Captured payment verification failed - payment will go on hold (temporary alert - ignore)';

        $slackArray = [
            'payment_id'    => $payment->getId(),
            'verified_at'   => $payment->getVerifyAt(),
            'verify_bucket' => $payment->getVerifyBucket(),
            'gateway'       => $payment->getGateway(),
            'status'        => $payment->getStatus(),
        ];

        $this->slack->queue(
            $message,
            $slackArray,
            [
                'channel' => $this->slackChannel,
            ]
        );
    }
}
