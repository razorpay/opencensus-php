<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Workflow\Service\Metric;
use RZP\Models\Workflow\Service\StateMap;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new StateMap\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws BadRequestException
     */
    public function create(array $input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::CREATE_STATE, $input);

        $stateId = $input[Entity::REQUEST_STATE_ID];

        $stateMap = $this->repo->workflow_state_map->getByStateId($stateId);

        if ($stateMap !== null)
        {
            $this->trace->count(Metric::WORKFLOW_STATE_MAP_CREATE_DUPLICATE_REQUEST_TOTAL);

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_STATE_CALLBACK_DUPLICATE,
                null,
                ['state_id' => $stateId]);
        }

        $stateMap = $this->core->create($input);

        return $stateMap->toArray();
    }

    /**
     * @param string $id
     * @param array $input
     * @return array
     * @throws BadRequestException
     */
    public function update(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::TYPEFORM_COMPLETE_RESPONSES_PARSING_ISSUE,
            [
                'id' => $id,
                'input' => $input
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::UPDATE_STATE, $input);

        $stateMap = $this->repo->workflow_state_map->getByStateId($id);

        if ($stateMap === null)
        {
            throw new BadRequestException(
                ErrorCode::SERVER_ERROR_WORKFLOW_STATE_ID_INVALID,
                null,
                ['id' => $id]);
        }

        $stateMap = $this->core->update($stateMap, $input, $id);

        return $stateMap->toArray();
    }
}
