<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\GatewayFile as GatewayFileJob;

class Core extends Base\Core
{
    /**
     * Creates a gateway file entity with input provided and processes it.
     *
     * @param  array $input input data
     *
     * @return PublicCollection
     */
    public function create(array $input): PublicCollection
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_CREATE_REQUEST, $input);

        $gatewayFiles = new PublicCollection();

        foreach ($input as $params)
        {
            $gatewayFile = (new Entity)->build($params);

            $this->repo->saveOrFail($gatewayFile);

            $gatewayFiles->push($gatewayFile);

            $target = $gatewayFile->getTarget();

            // If the request is made via cron, we do the processing
            // asynchronously via queue, else we do it in sync
            if (($this->app['basicauth']->isCron() === true) or ($this->isAsyncGateway($target) === true))
            {
                $this->processAsync($gatewayFile);
            }
            else
            {
                $this->process($gatewayFile);
            }

            $gatewayFile->reload();
        }

        $this->trace->info(TraceCode::GATEWAY_FILES_CREATED,
            $gatewayFiles->toArrayAdmin());

        return $gatewayFiles;
    }

    /**
     * Executes the steps required in generating and sending mail for
     * a gateway_file entity
     *
     * @param  Entity $gatewayFile Gateway file entity to be processed
     */
    public function process(Entity $gatewayFile)
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_PROCESSING,
            $gatewayFile->toArrayAdmin());

        $type = $gatewayFile->getType();
        $target = $gatewayFile->getTarget();

        $processor = $this->app['gateway_file']->getProcessor($type, $target);

        $processor->validateAndProcess($gatewayFile);
    }
    
    /**
     * Processes the input for acknowledging a gateway_file entity. This sets the
     * status to acknowledged and also fills in additional details like acknowledgement
     * timestamp and whether it is partially processed
     *
     * @param  Entity $gatewayFile gateway_file entity to acknowledge
     * @param  array  $data        Additional data for ack request
     *
     * @return Entity Acknowledged gateway file entity
     * @throws Exception\BadRequestValidationFailureException
     */
    public function acknowledge(Entity $gatewayFile, array $data): Entity
    {

        // Only gateway_file entities for which we have sent the file successfully
        // can be acknowledged
        if ($gatewayFile->isFileSent() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot acknoewledge given gateway_file entity before file is sent.');
        }

        $type = $gatewayFile->getType();

        $target = $gatewayFile->getTarget();

        $processor = $this->app['gateway_file']->getProcessor($type, $target);

        $processor->acknowledge($gatewayFile, $data);

        return $gatewayFile;
    }

    /**
     * Process the gateway_file entity via queue
     *
     * @param  Entity $gatewayFile gateway_file entity to process
     */
    protected function processAsync(Entity $gatewayFile)
    {
        GatewayFileJob::dispatch($gatewayFile->getId(), $this->mode);
    }

    protected function isAsyncGateway($target)
    {
        return in_array($target, Constants::ASYNC_GATEWAYS, true);
    }
}
