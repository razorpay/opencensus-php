<?php

namespace RZP\Models\Merchant\OwnerDetail;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function deleteOwnerDetail($id)
    {
        try
        {
            $ownerDetail = $this->repo->merchant_owner_details->findOrFail($id);
            $this->repo->merchant_owner_details->deleteOrFail($ownerDetail);

            $this->trace->info(TraceCode::MERCHANT_OWNER_DETAIL_DELETE,
                ['id' => $ownerDetail->getId()]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::MERCHANT_OWNER_DETAIL_DELETE_FAILED);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                TraceCode::MERCHANT_OWNER_DETAIL_DELETE_FAILED);
        }
        return ['owner_id' => $id];
    }
}
