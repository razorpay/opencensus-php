<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createLinkedAccountReferenceData(array $input)
    {
        $this->trace->info(TraceCode::LA_REFERENCE_DATA_CREATE_REQUEST_RECEIVED, [
            Entity::INPUT_COUNT   => count($input)
        ]);

        return $this->core()->createMany($input);
    }

    public function editLinkedAccountReferenceData(string $id, array $input)
    {
        $this->trace->info(TraceCode::AMC_LINKED_ACCOUNT_REFERENCE_DATA_UPDATE_REQUEST,
            [
                'id'        => $id,
                'input'     => $input
            ]
        );
        $response = [];

        try
        {
            $linkedAccountReferenceEntity = $this->repo->linked_account_reference_data->findOrFail($id);

            $this->core()->edit($linkedAccountReferenceEntity, $input);

            $this->trace->info(
                TraceCode::AMC_LINKED_ACCOUNT_REFERENCE_DATA_UPDATE_SUCCESSFUL,
                $linkedAccountReferenceEntity->toArray()
            );

            $response = $linkedAccountReferenceEntity->toArrayPublic();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            $response = [
                'status'        =>  'failed',
                'message'       =>  $e->getMessage(),
            ];
        }
        return $response;
    }
}
