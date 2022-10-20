<?php


namespace RZP\Gateway\Wallet\Razorpaywallet;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Constants\Entity as Constants;
use RZP\Models\Payment\Gateway as PaymentGateway;
use RZP\Models\Customer\Transaction\Entity as CustomerTransaction;

class Gateway extends Base\Gateway
{
    protected $gateway = Constants::WALLET_RAZORPAYWALLET;

    protected $topup = true;

    public function authorize(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'wallet_user_id'    => $input['payment']['reference14'] ?? null,
            ]
        );

        parent::authorize($input);

        $data = [
            'merchant_id'   => $input['merchant']->getId(),
            'payment_id'    => $input['payment']['id'],
            'amount'        => $input['payment']['amount'],
            'customer_consent' => true,
        ];

        if ((isset($input['payment']['notes']) === true) and
            ($input['payment']['notes'] !== []))
        {
            $data['notes'] = json_encode($input['payment']['notes']);
        }

        if ((isset($input['payment']['reference14']) === true)) {
            $data['user_id']  = $input['payment']['reference14'];
        }

        if ((isset($input['payment']['contact']) === true)) {
            $data['contact']  = $input['payment']['contact'];
        }

        $response = App::getFacadeRoot()['wallet_api']->payment($data);

        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
            [
                'gateway'       => $this->gateway,
                'payment_id'    => $input['payment']['id'],
                'response'      => $response,
            ]
        );
    }

    public function verifyRefund(array $input)
    {
        $scroogeResponse = new ScroogeResponse();

        return $scroogeResponse->setSuccess(false)
            ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
            ->toArray();
    }

    public function refund(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'gateway'           => $this->gateway,
                'refund_id'         => $input['refund']['id'],
                'wallet_user_id'    => $input['payment']['reference14'] ?? null,
            ]
        );

        parent::refund($input);

        $data = [
            'merchant_id'   => $input['merchant']->getId(),
            'user_id'       => $input['payment']['reference14'],
            'refund_id'     => $input['refund']['id'],
            'payment_id'    => $input['payment']['id'],
            'amount'        => $input['amount'],
        ];

        if ((isset($input['refund']['notes']) === true) and
            ($input['refund']['notes'] !== []))
        {
            $data['notes'] = json_encode($input['refund']['notes']);
        }

        $response = App::getFacadeRoot()['wallet_api']->refund($data);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'       => $this->gateway,
                'refund_id'     => $input['refund']['id'],
                'response'      => $response,
            ]
        );

        return [
            PaymentGateway::GATEWAY_RESPONSE    => json_encode($response),
            PaymentGateway::GATEWAY_KEYS        => $this->getGatewayData($response),
        ];
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                CustomerTransaction::STATUS => $response[CustomerTransaction::STATUS] ?? null,
            ];
        }

        return [];
    }

    public function capture(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_CAPTURE_REQUEST,
            [
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]
        );

        parent::capture($input);

        $data = [
            'payment_id'    => $input['payment']['id'],
            'amount'        => $input['payment']['amount'],
        ];

        $response = App::getFacadeRoot()['wallet_api']->capture($data);

        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
            [
                'gateway'       => $this->gateway,
                'payment_id'    => $input['payment']['id'],
                'response'      => $response,
            ]
        );
    }
}
