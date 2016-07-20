<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Olamoney\Action;
use RZP\Models\Payment\Status as PaymentStatus;
use Carbon\Carbon;
use RZP\Gateway\Wallet\Olamoney\RequestFields as RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields as ResponseFields;

trait Enquiry
{
    protected function verifyPayment($verify)
    {
        // api wallet gateway entity
        $walletPayment = $verify->payment;

        $input = $verify->input;

        // Response received from wallet gateway
        // Possible $verifyResponse status values - completed, failed, initialized, error
        $verifyResponse = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($verifyResponse[ResponseFields::STATUS] === Status::COMPLETED)
        {
            $this->checkVerifyStatusOnGatewaySuccess($walletPayment, $input, $verify);
        }
        else
        {
            $this->checkVerifyStatusOnGatewayFail($walletPayment, $input, $verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        if ($verify->match === false)
        {
            $verify->payment = $this->saveVerifyContent($walletPayment,
                                                        $verifyResponse);
        }

        return $verify->status;
    }

    protected function checkVerifyStatusOnGatewayFail($walletPayment, $input, $verify)
    {
        $verify->gatewaySuccess = false;

        $verifyResponse = $verify->verifyResponseContent;

        if (in_array($verifyResponse[ResponseFields::STATUS],
                array(Status::INITIATED, Status::FAILED)))
        {
            if (($walletPayment === null) and
                (($input['payment']['status'] === PaymentStatus::FAILED) or
                 ($input['payment']['status'] === PaymentStatus::CREATED)))
            {
                $verify->apiSuccess = false;
            }
            else if ($walletPayment !== null)
            {
                if (($walletPayment['received'] === false) and
                ($walletPayment['status_code'] === null or
                    $walletPayment['status_code'] !== Status::SUCCESS))
                {
                    $verify->apiSuccess = false;
                }
                else if ($walletPayment['status'] === Status::SUCCESS)
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->apiSuccess = true;
                }
            }
        }
    }

    protected function checkVerifyStatusOnGatewaySuccess($walletPayment, $input, $verify)
    {
        $verify->gatewaySuccess = true;

        // $input['payment'] is api payment entity
        if (($input['payment']['status'] !== PaymentStatus::CREATED) and
            ($input['payment']['status'] !== PaymentStatus::FAILED) and
            ($walletPayment !== null) and
            $walletPayment['status_code'] === Status::SUCCESS)
        {
            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
        }
    }

    protected function saveVerifyContent($walletPayment, $verifyResponse)
    {
        $this->action = Action::AUTHORIZE;

        if (isset($verifyResponse[ResponseFields::STATUS]) and
            ($verifyResponse[ResponseFields::STATUS] === Status::COMPLETED))
        {
            $walletAttributes = $this->getVerifyWalletCreateAttributes($verifyResponse);

            if ($walletPayment === null)
            {
                $walletPayment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if (($walletPayment['received'] === false) or
                ($walletPayment['status'] !== Status::SUCCESS))
            {
                $walletPayment->fill($walletAttributes);
                $walletPayment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $walletPayment;
    }

    protected function getVerifyWalletCreateAttributes($verifyResponse)
    {
        $payment = $this->input['payment'];

        $contentToSave = array(
            'amount'                => $payment['amount'],
            'received'              => true,
            'email'                 => $payment['email'],
            'contact'               => $this->getFormattedContact($payment['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($this->input['terminal']),
            'status'                => Status::SUCCESS,
            'transactionId'         => $verifyResponse['uniqueBillId'],
        );

        return $contentToSave;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        // $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => 'wallet_olamoney',
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getVerifyRequestArray($input)
    {
        $content = array(
            RequestFields::UNIQUE_BILL_ID   => $input['payment']['id'],
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::TIMESTAMP        => Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s'),
            );

        $content[RequestFields::HASH] = $this->getHashForVerifyRequest($content);

        $request = $this->getStandardRequestArray($content, 'GET');

        return $request;
    }

    protected function getHashForVerifyRequest($content)
    {
        $str = $content[RequestFields::ACCESS_TOKEN] . '|';
        $str .= $content[RequestFields::UNIQUE_BILL_ID] . '||';
        $str .= $content[RequestFields::TIMESTAMP] . '|||';
        $str .= $this->getSecret();

        return $this->getHashOfString($str);
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }
}