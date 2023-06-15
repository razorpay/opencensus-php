<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;

trait OtpResend
{
    public function otpResend($id, array $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $this->trace->info(
            TraceCode::PAYMENT_OTP_RESEND_REQUEST,
            [
                'input'         => $input,
                'payment_id'    => $id
            ]);

        try
        {
            $payment = $this->retrieve($id);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_OTP_RESEND_INITIATED, $payment);

            $this->validatePaymentStatus($payment);

            $gatewayInput = [];

            $this->prePaymentOtpResendProcessing($payment, $input, $gatewayInput);

            if (
                $this->canRunOtpPaymentFlow($payment) === true or
                Payment\Gateway::canRunOtpFlowViaNbPlus($payment) //for all the nbplus supported OPT flow
            )
            {
                $data = $this->runOtpResendFlow($gatewayInput, $payment);

                $payment->resetOtpAttempts();

                $this->repo->saveOrFail($payment);

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_OTP_RESEND_PROCESSED, $payment);

                return $data;
            }
            else
            {
                throw new Exception\LogicException(
                    'Gateway does not support OTP resend',
                    null,
                    [
                        'payment_id' => $id
                    ]);
            }
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_OTP_RESEND_PROCESSED, $payment, $ex);

            $this->app['segment']->trackPayment($payment, TraceCode::OTP_RESEND_EXCEPTION);

            throw $ex;
        }
    }

    protected function runOtpResendFlow($gatewayInput, $payment)
    {
        // Checking if the resend is called for headless otp
        if (($payment->isMethodCardOrEmi() === true) and
            (($payment->getAuthType() === Payment\AuthType::HEADLESS_OTP) or
             ($payment->getAuthType() === Payment\AuthType::IVR)  or
                ((($payment->getGateway() === Payment\Gateway::PAYSECURE) or ($payment->getGateway() === Payment\Gateway::AXIS_MIGS) or ($payment->getGateway() === Payment\Gateway::HITACHI) or ($payment->getGateway() === Payment\Gateway::KOTAK_DEBIT_EMI) or ($payment->getGateway() === Payment\Gateway::INDUSIND_DEBIT_EMI) or ($payment->getGateway() === Payment\Gateway::AXIS_TOKENHQ) or ($payment->getGateway()=== Payment\Gateway::ICICI)) and
                    ($payment->getAuthType() === Payment\AuthType::OTP))))
        {
            if ($payment->getCpsRoute() === Payment\Entity::CARD_PAYMENT_SERVICE)
            {
                $request = $this->callGatewayFunction(Payment\Action::OTP_RESEND, $gatewayInput);
            }
            else
            {
                $request = $this->resendHeadlessOtp($payment, $gatewayInput);
            }

            return $this->getOtpPaymentCreatedResponse($request, $payment);
        }
        if ($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE)
        {
            $request = $this->callGatewayFunction(Payment\Action::OTP_RESEND, $gatewayInput);

            return $this->getOtpPaymentCreatedResponse($request, $payment);
        }

        return $this->callGatewayOtpGenerate($gatewayInput, $payment, true);
    }

    protected function validatePaymentStatus($payment)
    {
        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        if ($payment->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
        }
    }

    protected function setCardAndMerchantDetails($payment,array &$input)
    {
        $card = $payment->card;

        //set card details
        $this->setCardNumberAndCvv($input,$card->toArray());

        $input['card']['expiry_month']  = $card->getExpiryMonth();

        $input['card']['expiry_year']   = $card->getExpiryYear();
        $input['card']['network_code']  = $card->getNetworkCode();

        //set merchant
        $input['merchant'] = $payment->merchant;

        //set Terminal
        $input['terminal'] = $payment->terminal;

        //set payment analytics
        $input['payment_analytics'] = $this->repo->payment_analytics->findLatestByPayment($payment->getId());

    }
    protected function prePaymentOtpResendProcessing($payment, $input, array & $gatewayInput)
    {
        $this->verifyPaymentMethodEnabled($payment);

        // Set metadata in payment
        $payment->setMetadata($input);

        //
        // Call gateway input
        //

        $gatewayInput['payment'] = $payment->toArray();

        //Otpresend for Ivr, paysecures requires the card details
        if (($payment->getAuthType() === Payment\AuthType::IVR) or
            ($payment->getGateway() === Payment\Gateway::PAYSECURE) or
            ($payment->getGateway() === Payment\Gateway::KOTAK_DEBIT_EMI) or
            ($payment->getGateway() === Payment\Gateway::ICICI)
        )
        {
            $this->setCardAndMerchantDetails($payment,$gatewayInput);
        }

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        $gatewayInput['otpSubmitUrl'] = $this->getOtpSubmitUrl();

        if ($payment->isEmi() === true)
        {
            $gatewayInput['emi_plan'] = $payment->emi;
        }
    }
}
