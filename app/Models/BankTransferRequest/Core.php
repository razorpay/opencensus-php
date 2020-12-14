<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(array $input, string $gateway, $requestPayload) : Entity
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_SAVE_REQUEST,
            [
                Entity::GATEWAY         => $gateway,
                Entity::TRANSACTION_ID  => $input[Entity::TRANSACTION_ID],
            ]
        );

        $requestPayload = json_encode($requestPayload);

        $bankTransferRequest = new Entity();

        $bankTransferRequest->findAndSetRequestSource();

        $input += [
            Entity::GATEWAY         => $gateway,
            Entity::REQUEST_PAYLOAD => $requestPayload,
        ];

        try
        {
            $bankTransferRequest->build($input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BANK_TRANSFER_SAVE_REQUEST_FAILED,
                [
                    Entity::GATEWAY         => $gateway,
                    Entity::TRANSACTION_ID  => $input[Entity::TRANSACTION_ID],
                ]
            );

            $bankTransferRequest->setGateway($gateway);

            $bankTransferRequest->setUtr($input[Entity::TRANSACTION_ID]);

            $bankTransferRequest->setRequestPayload($requestPayload);
        }

        $this->repo->saveOrFail($bankTransferRequest);

        $this->trace->info(
            TraceCode::BANK_TRANSFER_REQUEST_SAVED,
            [
                Entity::ID              => $bankTransferRequest->getPublicId(),
                Entity::GATEWAY         => $gateway,
                Entity::TRANSACTION_ID  => $input[Entity::TRANSACTION_ID],
            ]
        );

        return $bankTransferRequest;
    }
}
