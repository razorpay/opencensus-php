<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
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

    public function process(Entity $gatewayFile)
    {
        $procesor = ProcessorFactory::getProcessor($gatewayFile);

        $procesor->process();
    }

    public function generateGatewayRefundFiles()
    {
        $type = Type::REFUND;

        $gatewayFiles = new Base\PublicCollection;

        $gateways = Constants::SUPPORTED_GATEWAYS[$type];

        foreach ($gateways as $gateway)
        {
            $params = $this->getGatewayFileCreationParams($gateway, $type);

            $gatewayFile = $this->create($params, true);

            $gatewayFiles->push($gatewayFile);

            // TODO Move this step to queue later
            $this->process($gatewayFile);
        }

        return $gatewayFiles;
    }

    public function getGatewayFileCreationParams(string $gateway, string $type): array
    {
        // TODO: Change this later
        $from = Carbon::today('Asia/Kolkata')->timestamp;
        $to = Carbon::tomorrow('Asia/Kolkata')->timestamp - 1;

        $params = [
            Entity::TYPE      => $type,
            Entity::GATEWAY   => $gateway,
            Entity::FROM      => $from,
            Entity::TO        => $to,
            Entity::SCHEDULED => 1,
        ];

        return $params;
    }
}
