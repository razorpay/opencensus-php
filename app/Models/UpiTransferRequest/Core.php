<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, $requestPayload) : Entity
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_SAVE_REQUEST,
            [
                Entity::GATEWAY             => $input[Entity::GATEWAY],
                Entity::NPCI_REFERENCE_ID   => $input[Entity::NPCI_REFERENCE_ID],
            ]
        );

        $requestPayload = json_encode($requestPayload);

        $input[Entity::REQUEST_PAYLOAD] = $requestPayload;

        $upiTransferRequest = new Entity();

        try
        {
            $upiTransferRequest->build($input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $upiTransferRequest->setGateway($input[Entity::GATEWAY]);

            $upiTransferRequest->setNpciReferenceId($input[Entity::NPCI_REFERENCE_ID]);

            $upiTransferRequest->setRequestPayload($requestPayload);
        }

        $this->repo->saveOrFail($upiTransferRequest);

        return $upiTransferRequest;
    }
}
