<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $input = $this->formatInput($input);

        $gatewayFiles = $this->core()->create($input);

        return $gatewayFiles->toArrayAdmin();
    }

    public function acknowledge(string $id, array $data)
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_ACKNOWLEDGE_REQUEST, [
            'id'   => $id,
            'data' => $data,
        ]);

        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        $gatewayFile = $this->core()->acknowledge($gatewayFile, $data);

        return $gatewayFile->toArrayAdmin();
    }

    public function retry(string $id)
    {
        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        $this->core()->process($gatewayFile);

        return $gatewayFile->toArrayAdmin();
    }

    protected function formatInput(array $input): array
    {
        if ((isset($input['targets']) === false) or
            (is_sequential_array($input['targets']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'targets are required and should be sent');
        }

        $targets = $input['targets'];
        unset($input['targets']);

        $this->updateTimePeriodIfApplicable($input);

        foreach ($targets as $target)
        {
            $input[Entity::TARGET] = $target;

            $data[] = $input;
        }

        return $data;
    }

    protected function updateTimePeriodIfApplicable(array & $input)
    {
        // When called via cron, we update the timestamps for the gateway file for the
        // to indicate the previous days time period
        if ($this->app['basicauth']->isCron() === true)
        {
            $input[Entity::BEGIN] = Carbon::yesterday(Timezone::IST)->getTimestamp();

            $input[Entity::END] = Carbon::today(Timezone::IST)->getTimestamp() - 1;
        }
    }
}
