<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * Creates a gateway file entity with input provided
     *
     * @param  array        $input input data
     * @param  bool|boolean $halt  flag to indicate if the entity created should be further processed or not
     */
    public function create(array $input, bool $halt = false)
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
        $procesor = ProcessorFactory::getProcessor($gatewayFile);

        $procesor->process();
    }

    public function acknowledge(string $id, array $data)
    {
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

    public function generateGatewayFiles(string $type, array $data)
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
