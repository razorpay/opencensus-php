<?php

namespace RZP\Models\Survey;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws BadRequestException
     */
    public function create(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_CREATE, $input);

        // Check if same survey type is already present, throw error if yes
        if (isset($input[Entity::TYPE]) === true)
        {
            $type = $input[Entity::TYPE];

            $this->checkForDuplicateType($type);
        }

        $survey = $this->core->create($input);

        return $survey->toArrayPublic();
    }

    /**
     * @param string $id , array $input
     * @param array $input
     * @return array
     * @throws BadRequestException
     */
    public function edit(string $id, array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_EDIT, $input);

        $survey = $this->repo->survey->findOrFailPublic($id);

        // Check if same survey type is already present, throw error if yes
        if (isset($input[Entity::TYPE]) === true)
        {
            $type = $input[Entity::TYPE];

            $this->checkForDuplicateType($type);
        }

        $survey = $this->core->edit($survey, $input);

        return $survey->toArrayPublic();
    }

    private function checkForDuplicateType(string $type)
    {
        $survey = $this->repo->survey->get($type);

        if (empty($survey) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_DUPLICATE_SURVEY_TYPE,
                null,
                [
                    Entity::TYPE  => $type,
                ]);
        }
    }
}
