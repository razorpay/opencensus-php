<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use Carbon\Carbon;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function handleValidateVpaResponse(array $input)
    {
        // For few test suites we have disabled this database
        if (env('DB_UPI_PAYMENTS_MOCKED') === true)
        {
            return;
        }

        $success = array_pull($input, 'success');

        // We are starting with saving the valid VPA only
        if ($success === false)
        {
            return;
        }

        // To merchant the response send has customer name
        $input[Entity::NAME]        = array_pull($input, 'customer_name');
        // For Validate VPA response current time can considered as received time
        $input[Entity::RECEIVED_AT] = Carbon::now()->getTimestamp();
        // Since we are skipping saving for invalid VPAs
        $input[Entity::STATUS]      = Status::VALID;

        return $this->updateOrCreate($input);
    }

    public function updateOrCreate(array $input)
    {
        try
        {
            return (new Core())->updateOrCreate($input);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);
        }
    }
}
