<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;

class Core extends Base\Core
{
    /**
     * Creates a gateway file entity with input provided and processes it.
     * Here the halt flag represents if we should continue with processing the entity
     * in sync or just create and return the entity to be processed later. This is
     * used when we want to asynchronously process the entity via queue.
     *
     * @param  array        $input input data
     * @param  bool|boolean $halt  flag to indicate if the entity created should be further processed or not
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

            $this->process($gatewayFile);
        }

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
        $this->trace->info(TraceCode::GATEWAY_FILE_PROCESSING, [
            'id'     => $gatewayFile->getId(),
            'target' => $gatewayFile->getTarget(),
        ]);

        $type = $gatewayFile->getType();
        $target = $gatewayFile->getTarget();

        $processor = $this->app['gateway_file']->getProcessor($type, $target);

        $processor->process($gatewayFile);
    }

    /**
     * Processes the input for acknowledging a gateway_file entity. This sets the
     * status to acknowledged and also fills in additional details like acknowledgement
     * timestamp and whether it is partially processed
     *
     * @param  Entity $gatewayFile   gateway_file entity to acknowledge
     * @param  array  $data          Additional data for ack request
     * @return Entuty                Acknowledged gateway file entuty
     */
    public function acknowledge(Entity $gatewayFile, array $data): Entity
    {

        // Only gateway_file entities for which we have sent a mail successfully
        // can be acknowledged
        if ($gatewayFile->isMailSent() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot acknoewledge given gateway_file entity');
        }

        $type = $gatewayFile->getType();

        $target = $gatewayFile->getTarget();

        $processor = $this->app['gateway_file']->getProcessor($type, $target);

        $processor->acknowledge($gatewayFile, $data);

        return $gatewayFile;
    }
}
