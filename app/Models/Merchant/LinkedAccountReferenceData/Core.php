<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input)
    {
        (new Validator)->validateInput('create', $input);

        $this->trace->info(TraceCode::LA_REFERENCE_DATA_CREATION_INITIATED, [
            "account_name" => array_get($input, Entity::ACCOUNT_NAME)
        ]);

        $la_ref_entity = (new Entity)->generateId();

        $la_ref_entity->build($input);

        $this->repo->transaction(function() use ($la_ref_entity){

            $this->repo->saveOrFail($la_ref_entity);

        });

        $this->trace->info(TraceCode::LA_REFERENCE_DATA_CREATED, [
            "input" => $input
        ]);

        return $la_ref_entity;
    }

    public function createMany(array $input): array
    {
        (new Validator)->validateInput('create_many', $input);

        $input = array_get($input, Entity::LA_REFERENCE_DATA);

        $successRefData = [];

        $failedRefData = [];

        foreach($input as $la_ref_data)
        {
            try
            {
                $entity = $this->create($la_ref_data);

                array_push($successRefData, $entity->getBusinessName());
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e, null, TraceCode::LA_REFERENCE_DATA_CREATION_FAILED);

                array_push($failedRefData, $la_ref_data[Entity::BUSINESS_NAME]);
            }
        }

        $response = [
            Entity::SUCCESS     => count($successRefData),
            Entity::FAILURE     => count($failedRefData),
            Entity::TOTAL       => sizeof($input),
            Entity::DATA        => [
                Entity::SUCCESSFUL  => $successRefData,
                Entity::FAILED      => $failedRefData,
            ]
        ];

        return $response;
    }

    public function edit(Entity $linkedAccountReferenceData, array $input) : Entity
    {
        $linkedAccountReferenceData->edit($input);

        $this->repo->saveOrFail($linkedAccountReferenceData);

        return $linkedAccountReferenceData;
    }
}
