<?php

namespace RZP\Gateway\Worldline\Mock;

use Carbon\Carbon;
use function GuzzleHttp\default_ca_bundle;
use phpseclib\Crypt\AES;

use RZP\Models\Currency;
use RZP\Gateway\Base;
use RZP\Gateway\Worldline\Fields;

class Server extends Base\Mock\Server
{
    protected $aesCrypto;

    public function fillBharatQrCallback(& $request , $qrCode)
    {
        if ($qrCode !== null)
        {
            $request[Fields::PRIMARY_ID]    = $qrCode;
            $request[Fields::SECONDARY_ID]  = $qrCode . '_' . $request[Fields::SECONDARY_ID];
        }

        if (isset($request[Fields::CONSUMER_PAN]))
        {
            $encryptedCardNumber =  $this->encryptAes($request[Fields::CONSUMER_PAN]);

            $request[Fields::CONSUMER_PAN] = $encryptedCardNumber;
        }
    }

    public function verify($input)
    {
        $input = json_decode($input[Fields::PARM], true);

        parent::verify($input);

        $this->validateActionInput($input);

        $response  = $this->getVerifyResponse($input);

        return $this->makeResponse($response);
    }

    protected function getVerifyResponse($input)
    {
        switch (substr($input[Fields::DATA][Fields::TR_ID],-4))
        {
            case 'CARD':
                $attributes = [
                    Fields::STATUS          => 'SUCCESS',
                    Fields::MESSAGE         => '',
                    Fields::RESPONSE_OBJECT => [
                        Fields::MID                 => '037122003842039',
                        Fields::M_PAN               => '4604901004774122',
                        Fields::CUSTOMER_NAME       => 'Vishnu',
                        Fields::TXN_CURRENCY        => Currency\Currency::INR,
                        Fields::TXN_AMOUNT          => $input[Fields::DATA][Fields::AMOUNT],
                        Fields::AUTH_CODE           => 'AUTH',
                        Fields::REF_NO              => '721304414190',
                        Fields::PRIMARY_ID          => $input[Fields::DATA][Fields::TXN_ID],
                        Fields::SECONDARY_ID        => $input[Fields::DATA][Fields::TR_ID],
                        Fields::SETTLEMENT_AMOUNT   => $input[Fields::DATA][Fields::AMOUNT],
                        Fields::TIME_STAMP          => '20170801093103',
                        Fields::TRANSACTION_TYPE    => '1',
                        Fields::BANK_CODE           => '00031',
                        Fields::AGGREGATOR_ID       => 'AG1',
                        Fields::CONSUMER_PAN        => $this->encryptAes('438628xxxxxx3456'),
                    ]
                ];

                break;

            case 'UPIT':
                $attributes = [
                    Fields::STATUS          => 'SUCCESS',
                    Fields::MESSAGE         => '',
                    Fields::RESPONSE_OBJECT => [
                        Fields::TXN_CURRENCY        => Currency\Currency::INR,
                        Fields::TXN_AMOUNT          => $input[Fields::DATA][Fields::AMOUNT],
                        Fields::REF_NO              => '721304414190',
                        Fields::PRIMARY_ID          => $input[Fields::DATA][Fields::TXN_ID],
                        Fields::SECONDARY_ID        => $input[Fields::DATA][Fields::TR_ID],
                        Fields::SETTLEMENT_AMOUNT   => $input[Fields::DATA][Fields::AMOUNT],
                        Fields::TIME_STAMP          => '20170801093103',
                        Fields::TRANSACTION_TYPE    => '2',
                        Fields::BANK_CODE           => '00031',
                        Fields::AGGREGATOR_ID       => 'AG1',
                        Fields::MID                 => '037122003842039',
                        Fields::MERCHANT_VPA        => 'razorpay@axis',
                        Fields::CUSTOMER_VPA        => 'vishnu@icici',
                    ]
                ];

                break;
        }

        $this->content($attributes, $this->action);

        return $attributes;
    }

    protected function createCryptoIfNotCreated()
    {
        $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $this->getGatewayInstance()->getSecret());
    }

    public function encryptAes(string $stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->encryptString($stringToEncrypt);
    }

    protected function getGatewayInstance($bankingType = null)
    {
        $class = 'RZP\Gateway\Worldline\Gateway';

        $gateway = new $class;

        return $gateway;
    }

    public function refund($input)
    {
        $input = json_decode($input[Fields::PARM], true);

        parent::refund($input);

        $this->validateActionInput($input);

        $response  = $this->getRefundResponse($input);

        return $this->makeResponse($response);
    }

    protected function getRefundResponse($input)
    {
        $attributes = [
            Fields::STATUS          => 'Success',
            Fields::RESPONSE_OBJECT => [
                Fields::TID               => $input[Fields::DATA][Fields::TID],
                Fields::RRN               => $input[Fields::DATA][Fields::RRN],
                Fields::REFUND_AMOUNT     => $input[Fields::DATA][Fields::REFUND_AMOUNT],
                Fields::REFUND_TXN_AMOUNT => $input[Fields::DATA][Fields::REFUND_AMOUNT],
                Fields::REFUND_ID         => $input[Fields::DATA][Fields::REFUND_ID],
            ]
        ];

        $this->content($attributes, $this->action);

        return $attributes;
    }

    public function getBharatQrCallbackForRecon($qrCodeId)
    {
        return $this->getBharatQrCallback($qrCodeId, 123456789012);
    }

    public function getBharatQrCallback($qrCodeId, $ref = null, $input = [])
    {
        $data = [
            Fields::MID                 => '037122003842039',
            Fields::M_PAN               => '4604901004774122',
            Fields::CUSTOMER_NAME       => 'Vishnu',
            Fields::TXN_CURRENCY        => Currency\Currency::INR,
            Fields::TXN_AMOUNT          => '200.00',
            Fields::AUTH_CODE           => 'AUTH',
            Fields::REF_NO              => '721304414190',
            Fields::PRIMARY_ID          => $qrCodeId,
            Fields::SECONDARY_ID        => 'CARD',
            Fields::SETTLEMENT_AMOUNT   => '200.00',
            Fields::TIME_STAMP          => '20170801093103',
            Fields::TRANSACTION_TYPE    => '1',
            Fields::BANK_CODE           => '00031',
            Fields::AGGREGATOR_ID       => 'AG1',
            Fields::CONSUMER_PAN        => $this->encryptAes('438628xxxxxx3456'),
        ];

        return $data;
    }
}
