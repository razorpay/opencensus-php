<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createMerchantInternationalIntegration($input)
    {
        try
        {

            $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $input[Entity::MERCHANT_ID], $input[Entity::INTEGRATION_ENTITY]);

            if(isset($mii))
            {
                throw new \Exception("Merchant Intgration Entity already exists");
            }
            else{
                $this->createNewIntegration($input);
            }
        }
        catch(\Throwable $e)
        {
            {
                $this->trace->traceException($e);

                $this->trace->info(
                    TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_SAVE_FAILED,
                    ['input' => $input]
                );

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null,
                    $e->getMessage()
                );
            }
        }

        return [
            'merchant_id'             => $input[Entity::MERCHANT_ID],
            'integration_entity'      => $input[Entity::INTEGRATION_ENTITY],
        ];
    }

    protected function createNewIntegration(array $input)
    {
        $merchantIntegration = new Entity;

        $merchantIntegration->generateId();

        $merchantIntegration->build($input);

        $this->repo->merchant_international_integrations->saveOrFail($merchantIntegration);

        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_CREATE, [
            'merchant_id'             => $input[Entity::MERCHANT_ID],
            'integration_entity'      => $input[Entity::INTEGRATION_ENTITY],
        ]);

        return $merchantIntegration;
    }

    public function deleteMerchantInternationalIntegration(array $input)
    {
        try
        {
            $mid = $input[Entity::MERCHANT_ID];
            $integration_entity = $input[Entity::INTEGRATION_ENTITY];

            $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $integration =  $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $mid, $integration_entity);

            if(!isset($integration)){
                throw new \Exception("Invalid request");
            }

            $this->trace->info(
                TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_DELETE,
                ['input' => $input]
            );

            $this->repo->deleteOrFail($integration);
        }
        catch(\Exception $e)
        {

            $this->trace->info(
                TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_DELETE_FAILED,
                ['input' => $input, 'message' => $e->getMessage(), 'trace' => $e->getTrace()]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                $e->getMessage()
            );
        }

        return ['success' => 'true'];
    }
}
