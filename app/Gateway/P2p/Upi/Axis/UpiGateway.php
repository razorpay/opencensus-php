<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiTransactionTransformer;

class UpiGateway extends Gateway implements Contracts\UpiGateway
{
    public function initiateGatewayCallback(Response $response)
    {
        $content = $this->input->get(Fields::CONTENT);
        $type    = $content[Fields::TYPE];

        switch ($type)
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:

                $transformer = new UpiTransactionTransformer($content, $type);
                $upi = $transformer->transformIncoming();

                $transformer = new TransactionTransformer($upi, $type);
                $transaction = $transformer->transformIncoming();

                $context = [
                    Transaction\Entity::ENTITY      => Transaction\Entity::TRANSACTION,
                    Transaction\Entity::ACTION      => Transaction\Action::INCOMING_COLLECT,
                ];

                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:

                $transformer = new UpiTransactionTransformer($content, $type);
                $upi = $transformer->transformIncoming();

                $transformer = new TransactionTransformer($upi, $type);
                $transaction = $transformer->transformIncoming();

                $context = [
                    Transaction\Entity::ENTITY      => Transaction\Entity::TRANSACTION,
                    Transaction\Entity::ACTION      => Transaction\Action::INCOMING_PAY,
                ];

                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_PAY:

                $transformer = new UpiTransactionTransformer($content, $type);
                $upi = $transformer->transformCallback();

                $transformer = new TransactionTransformer($upi, $type);
                $transaction = $transformer->transformCallback();

                $context = [
                    Transaction\Entity::ENTITY      => Transaction\Entity::TRANSACTION,
                    Transaction\Entity::ACTION      => Transaction\Action::AUTHORIZE_TRANSACTION_SUCCESS,
                ];

                break;
        }

        $response->setData([
            Transaction\Entity::TRANSACTION     => $transaction,
            Transaction\Entity::UPI             => $upi,
            Transaction\Entity::CONTEXT         => $context,
        ]);
    }

    public function gatewayCallback(Response $response)
    {
        $gatewayData = $this->input->get(Transaction\Entity::GATEWAY_DATA);

        switch ($this->input->get(Fields::CONTENT)[Fields::TYPE])
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:
            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:
            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_PAY:

                $signature = $this->getpayloadSignature();
                $payload   = $this->input->get(Fields::PAYLOAD);

                $verifier = $this->getMerchantVerifier();

                if ($verifier->verify($payload, hex2bin($signature)) === false)
                {
                    throw $this->p2pGatewayException(ErrorMap::INVALID_SIGNATURE, [
                        'signature' => $signature,
                        'payload'   => $payload,
                    ]);
                }
                break;

            default:
                throw $this->p2pGatewayException(ErrorMap::INVALID_CALLBACK, [
                    'input' => $this->input->toArray(),
                ]);

        }

        $gatewayData[Transaction\Entity::RESPONSE] = [
            Transaction\Entity::SUCCESS => true,
        ];

        $response->setData($gatewayData);
    }

    protected function getpayloadSignature()
    {
        $headers = $this->input->get(Fields::HEADERS);

        $signature = $headers[Fields::X_MERCHANT_PAYLOAD_SIGNATURE] ?? null;

        return $signature[0] ?? $signature;
    }
}
