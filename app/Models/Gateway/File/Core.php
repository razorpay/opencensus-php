<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

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
    public function create(array $input, bool $halt = false): Entity
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_CREATE_REQUEST, $input);

        $gatewayFile = (new Entity)->build($input);

        $this->repo->saveOrFail($gatewayFile);

        if ($halt !== true)
        {
            $this->process($gatewayFile);
        }

        return $gatewayFile;
    }

    /**
     * Executines the steps required in generating and sending mail for
     * a gateway_file entity
     *
     * @param  Entity $gatewayFile Gateway file entity to be processed
     */
    public function process(Entity $gatewayFile)
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_PROCESSING, [
            'id'      => $gatewayFile->getId(),
            'gateway' => $gatewayFile->getGateway(),
            'bank'    => $gatewayFile->getBank()
        ]);

        $type = $gatewayFile->getType();
        $gateway = $gatewayFile->getGateway();
        $bank = $gatewayFile->getBank();

        $processor = $this->app['gateway']->getFileProcessor($type, $gateway, $bank);

        $processor->process($gatewayFile);
    }

    /**
     * Processes the input for acknowledging a gateway_file entity. This sets the
     * status to acknowledged and also fills in additional details like acknowledgement
     * timestamp and whether it is partially processed
     *
     * @param  string $id   gateway_file id to acknowledge
     * @param  array  $data Additional data for ack requesyt
     * @return [type]       [description]
     */
    public function acknowledge(string $id, array $data): Entity
    {
        $this->trace->info(TraceCode::GATEWAY_ACKNOWLEDGE_REQUEST, [
            'id'   => $id,
            'data' => $data,
        ]);

        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        // Only gateway_file entities for which we have sent a mail successfully
        // can be acknowledged
        if ($this->gatewayFile->isMailSent() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot acknoewledge given gateway_file entity');
        }

        $gatewayFile->getValidator()->validateInput('acknowledge', $data);

        $gatewayFile->setStatus(Status::ACKNOWLEDGED);

        $gatewayFile->setAcknowledgedAt(time());

        $gatewayFile->fill($data);

        $this->repo->saveOrFail($gatewayFile);

        return $gatewayFile;
    }

    /**
     * Generates multiple gateway files for given type and <gateway> => <bank>
     * combination. Here we create the base gateway_file entities in created state
     * and then process the files. Currently processing is happening in sync. We
     * can later change this to push to a queue and process asynchronously
     *
     * @param  string $type Type of the gateway_file entitiss to be processed
     * @param  array  $data Array containing gateway => bank mapping for
     * @return PublicCollection     Collection of gateway_file entities created
     */
    public function generateGatewayFiles(string $type, array $data): Base\PublicCollection
    {
        if (Type::isValidType($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$type is not a supported type");
        }

        $gatewayFiles = new Base\PublicCollection;

        foreach ($data as $gateway => $bank)
        {
            $params = $this->getGatewayFileCreationParams($type, $gateway, $bank);

            // We just create the entity here and process it in a separate state.
            // Hence the "halt" flag is passed as true
            $gatewayFile = $this->create($params, true);

            $gatewayFiles->push($gatewayFile);

            // TODO Move this step to queue later
            $this->process($gatewayFile);
        }

        return $gatewayFiles;
    }

    protected function getGatewayFileCreationParams(string $type, string $gateway, string $bank): array
    {
        // TODO: Change this later
        $from = Carbon::today('Asia/Kolkata')->timestamp;
        $to = Carbon::tomorrow('Asia/Kolkata')->timestamp - 1;

        $params = [
            Entity::TYPE      => $type,
            Entity::GATEWAY   => $gateway,
            Entity::BANK      => $bank,
            Entity::FROM      => $from,
            Entity::TO        => $to,
            Entity::SCHEDULED => 1,
        ];

        return $params;
    }
}
