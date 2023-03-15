<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;

class Service extends Base\Service
{

    const BLOCK_VALIDATE_VPA_DB_WRITES = 'block_validate_vpa_db_writes';

    public function handleValidateVpaRequest(array $input)
    {
        // For few test suites we have disabled this database
        if (env('DB_UPI_PAYMENTS_MOCKED') === true)
        {
            return null;
        }

        $vpa = $this->firstByAddress($input[Entity::VPA]);

        if (($vpa instanceof Entity) === false)
        {
            return null;
        }

        if ($vpa->isExpired() === true)
        {
            return null;
        }

        if ($vpa->isValid() === false)
        {
            return null;
        }

        return $vpa;
    }

    public function handleValidateVpaResponse(array $input)
    {
        // For few test suites we have disabled this database
        if (env('DB_UPI_PAYMENTS_MOCKED') === true)
        {
            return;
        }

        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
            self::BLOCK_VALIDATE_VPA_DB_WRITES,
            $this->app['rzp.mode'] ?? Mode::LIVE,
            3,
            [
                'connect_timeout' => 1,
                'timeout'         => 1,
            ]);

        if ($variant == 'on')
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

    public function firstByAddress($address)
    {
        try
        {
            return (new Core())->firstByAddress($address);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);
        }
    }
}
