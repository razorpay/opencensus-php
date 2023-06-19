<?php

namespace RZP\Models\UpiTransferRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(array $input, $requestPayload): Entity
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_SAVE_REQUEST,
            [
                Entity::GATEWAY           => $input[Entity::GATEWAY],
                Entity::NPCI_REFERENCE_ID => $input[Entity::NPCI_REFERENCE_ID],
            ]
        );

        $requestPayload = json_encode($requestPayload);

        $input[Entity::REQUEST_PAYLOAD] = $requestPayload;

        if (isset($input['payer_account_type']) === true)
        {
            // payer account type is not required in upi transfer request entity
            unset($input['payer_account_type']);
        }

        $upiTransferRequest = new Entity();

        try
        {
            $upiTransferRequest->findAndSetRequestSource();

            $upiTransferRequest->build($input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_TRANSFER_SAVE_REQUEST_FAILED,
                [
                    Entity::GATEWAY           => $input[Entity::GATEWAY],
                    Entity::NPCI_REFERENCE_ID => $input[Entity::NPCI_REFERENCE_ID],
                ]
            );

            $upiTransferRequest->setGateway($input[Entity::GATEWAY]);

            $upiTransferRequest->setNpciReferenceId($input[Entity::NPCI_REFERENCE_ID]);

            $upiTransferRequest->setRequestPayload($requestPayload);
        }

        $this->repo->saveOrFail($upiTransferRequest);

        $this->trace->info(
            TraceCode::UPI_TRANSFER_REQUEST_SAVED,
            [
                Entity::ID                => $upiTransferRequest->getPublicId(),
                Entity::GATEWAY           => $upiTransferRequest->getGateway(),
                Entity::NPCI_REFERENCE_ID => $upiTransferRequest->getNpciReferenceId(),
            ]
        );

        return $upiTransferRequest;
    }

    public function updateUpiTransferRequest(array $upiTransferInput, bool $isCreated, string $errorMessage = null,
                                             $upiTransferRequestId = null)
    {
        $data = [
            Entity::IS_CREATED    => $isCreated,
            Entity::ERROR_MESSAGE => substr($errorMessage, 0, 255),
        ];

        $gateway         = $upiTransferInput[Entity::GATEWAY];
        $npciReferenceId = $upiTransferInput[Entity::NPCI_REFERENCE_ID];

        try
        {
            if ($upiTransferRequestId !== null)
            {
                $upiTransferRequest = $this->repo->upi_transfer_request->findByPublicId($upiTransferRequestId);

                $upiTransferRequest->setIsCreated($isCreated);
                $upiTransferRequest->setErrorMessage(substr($errorMessage, 0, 255));

                $upiTransferRequest->save();

                return;
            }

            $this->repo
                ->upi_transfer_request
                ->updateByGatewayAndNpciRefId($gateway, $npciReferenceId, $data);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_TRANSFER_REQUEST_ADD_ERROR_MSG_FAILED,
                [
                    'gateway'           => $gateway,
                    'npci_reference_id' => $npciReferenceId,
                    'is_created'        => $isCreated,
                    'error_message'     => $errorMessage,
                ]
            );
        }
    }
}
