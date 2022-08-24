<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Mandate;
use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\MandateTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiMandateTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiTransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionConcernTransformer;

class UpiGateway extends Gateway implements Contracts\UpiGateway
{
    public function initiateGatewayCallback(Response $response)
    {
        $content = $this->input->get(Fields::CONTENT);
        $type    = $content[Fields::TYPE] ?? null;

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
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT:
                $transformer = new UpiTransactionTransformer($content, $type);
                $upi = $transformer->transformCallback();

                $transformer = new TransactionTransformer($upi, $type);
                $transaction = $transformer->transformCallback();

                $context = [
                    Transaction\Entity::ENTITY      => Transaction\Entity::TRANSACTION,
                    Transaction\Entity::ACTION      => Transaction\Action::AUTHORIZE_TRANSACTION_SUCCESS,
                ];

                break;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
                $upiMandateTransformer = new UpiMandateTransformer($content, $type);
                $upi                   = $upiMandateTransformer->transformIncoming();

                $mandateTransformer = new MandateTransformer($upi, $type);
                $mandate            = $mandateTransformer->transformIncoming();

                unset($upi[Mandate\Entity::MANDATE]);

                $context = [
                    Mandate\Entity::ENTITY  => Mandate\Entity::MANDATE,
                    Mandate\Entity::ACTION  => Mandate\Action::INCOMING_COLLECT,
                ];

                $response->setData([
                    Mandate\Entity::MANDATE => $mandate,
                    Mandate\Entity::UPI     => $upi,
                    Mandate\Entity::CONTEXT => $context,
                ]);

                return;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:

                $upiMandateTransformer = new UpiMandateTransformer($content, $type);
                $upi                   = $upiMandateTransformer->transformIncoming();

                $mandateTransformer = new MandateTransformer($upi, $type);
                $mandate            = $mandateTransformer->transformIncoming();

                unset($upi[Mandate\Entity::MANDATE]);

                $context = [
                    Mandate\Entity::ENTITY  => Mandate\Entity::MANDATE,
                    Mandate\Entity::ACTION  => Mandate\Action::INCOMING_UPDATE,
                ];

                $response->setData([
                       Mandate\Entity::MANDATE => $mandate,
                       Mandate\Entity::UPI     => $upi,
                       Mandate\Entity::CONTEXT => $context,
                ]);

                return;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED:

                $upiMandateTransformer = new UpiMandateTransformer($content, $type);
                $upi                   = $upiMandateTransformer->transformIncoming();

                $mandateTransformer = new MandateTransformer($upi, $type);
                $mandate            = $mandateTransformer->transformIncoming();

                unset($upi[Mandate\Entity::MANDATE]);

                $context = [
                    Mandate\Entity::ENTITY  => Mandate\Entity::MANDATE,
                    Mandate\Entity::ACTION  => Mandate\Action::INCOMING_PAUSE,
                ];

                $response->setData([
                           Mandate\Entity::MANDATE => $mandate,
                           Mandate\Entity::UPI     => $upi,
                           Mandate\Entity::CONTEXT => $context,
                ]);

                return;

            case UpiAction::MANDATE_STATUS_UPDATE:

                $upiMandateTransformer = new UpiMandateTransformer($content, $type);
                $upi                   = $upiMandateTransformer->transformIncoming();

                $mandateTransformer = new MandateTransformer($upi, $type);
                $mandate            = $mandateTransformer->transformIncoming();

                unset($upi[Mandate\Entity::MANDATE]);

                $context = [
                    Mandate\Entity::ENTITY  => Mandate\Entity::MANDATE,
                    Mandate\Entity::ACTION  => Mandate\Action::MANDATE_STATUS_UPDATE,
                ];

                $response->setData([
                   Mandate\Entity::MANDATE => $mandate,
                   Mandate\Entity::UPI     => $upi,
                   Mandate\Entity::CONTEXT => $context,
                ]);
                return;

            default:
                // In if axis does not send the type, which is for queries
                if (is_array(array_get($content, Fields::QUERIES)) === true)
                {
                    $concerns = [];

                    foreach ($content[Fields::QUERIES] as $query)
                    {
                        $transformer = new TransactionConcernTransformer($query, TransactionAction::QUERY_STATUS);
                        $concerns[] = $transformer->transformCallback();
                    }

                    $response->setData([
                        Transaction\Entity::CONCERNS    => $concerns,
                        Transaction\Entity::CONTEXT     => [
                            Transaction\Entity::ENTITY      => Transaction\Entity::CONCERNS,
                            Transaction\Entity::ACTION      => Transaction\Action::CONCERN_STATUS_SUCCESS,
                        ],
                    ]);

                    return;
                }
                else
                {
                    throw $this->p2pGatewayException(ErrorMap::INVALID_CALLBACK);
                }
        }

        $response->setData([
            Transaction\Entity::TRANSACTION     => $transaction,
            Transaction\Entity::UPI             => $upi,
            Transaction\Entity::CONTEXT         => $context,
        ]);
    }

    public function gatewayCallback(Response $response)
    {
        $input = $this->input->get(Transaction\Entity::REQUEST);

        switch ($input[Fields::CONTENT][Fields::TYPE] ?? null)
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:
            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:
            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT:
            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:
            case UpiAction::CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED:
            case UpiAction::MANDATE_STATUS_UPDATE:
            case null:

                $signature = $this->getpayloadSignature();
                $payload   = $input[Fields::PAYLOAD];

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
                    'input' => $input->toArray(),
                ]);
        }

        $this->input->put(Transaction\Entity::RESPONSE, [
            Transaction\Entity::SUCCESS => true,
        ]);

        $response->setData($this->input->toArray());
    }

    protected function getpayloadSignature()
    {
        $headers = $this->input->get(Transaction\Entity::REQUEST)[Fields::HEADERS];

        $signature = $headers[Fields::X_MERCHANT_PAYLOAD_SIGNATURE] ?? null;

        return $signature[0] ?? $signature;
    }
}
