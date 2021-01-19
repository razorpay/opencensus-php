<?php

namespace RZP\Models\Survey;

use RZP\Exception;
use RZP\Models\Base;

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
     */
    public function create(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_CREATE, $input);

        $survey = $this->core->create($input);

        return $survey->toArrayPublic();
    }
}
