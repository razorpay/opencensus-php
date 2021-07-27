<?php

namespace RZP\Models\BankTransferRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(array $input, string $gateway, $requestPayload, array $requestSource = []) : Entity
    {
        $bankTransferRequest = new Entity();

        if (empty($requestSource) === false)
        {
            $bankTransferRequest->setRequestSource(json_encode($requestSource));
        }
        else
        {
            $bankTransferRequest->findAndSetRequestSource();
        }

        $requestSource = $bankTransferRequest->getRequestSource();

        $this->trace->info(
            TraceCode::BANK_TRANSFER_SAVE_REQUEST,
            [
                Entity::GATEWAY        => $gateway,
                Entity::TRANSACTION_ID => $input[Entity::TRANSACTION_ID],
                Entity::REQUEST_SOURCE => $requestSource,
            ]
        );

        $requestPayload = json_encode($requestPayload);

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
                    Entity::GATEWAY        => $gateway,
                    Entity::TRANSACTION_ID => $input[Entity::TRANSACTION_ID],
                    Entity::REQUEST_SOURCE => $requestSource,
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
                Entity::ID             => $bankTransferRequest->getPublicId(),
                Entity::GATEWAY        => $gateway,
                Entity::TRANSACTION_ID => $input[Entity::TRANSACTION_ID],
                Entity::REQUEST_SOURCE => $requestSource,
            ]
        );

        return $bankTransferRequest;
    }

    public function updateBankTransferRequest(
        string $utr,
        bool $isCreated,
        string $errorMessage = null,
        Entity $bankTransferRequest = null
    )
    {
        try
        {
            if ($bankTransferRequest !== null)
            {
                $bankTransferRequest->setIsCreated($isCreated);
                $bankTransferRequest->setErrorMessage(substr($errorMessage, 0, 255));

                $bankTransferRequest->save();
            }
            else
            {
                $data = [
                    Entity::IS_CREATED    => $isCreated,
                    Entity::ERROR_MESSAGE => substr($errorMessage, 0, 255),
                ];

                $this->repo
                    ->bank_transfer_request
                    ->updateByUtr($utr, $data);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_REQUEST_UPDATION_FAILED,
                [
                    'utr'           => $utr,
                    'is_created'    => $isCreated,
                    'error_message' => $errorMessage
                ]
            );
        }
    }
}
