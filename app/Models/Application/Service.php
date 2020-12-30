<?php

namespace RZP\Models\Application;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Application;
use RZP\Models\Base;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new Application\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_CREATE_APP, $input);

        $duplicateApp = $this->checkForDuplicate($input);

        if ($duplicateApp === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_APP_ALREADY_EXIST,
                null,
                $input
            );
        }

        $app = $this->core->create($input);

        return $app->toArrayPublic();
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function update(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_UPDATE_APP, $input);

        /** @var Entity $app */
        $app = $this->repo->application->findOrFailPublic($input[Entity::ID]);

        $duplicateApp = $this->checkForDuplicate($input);

        if ($duplicateApp === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_APP_ALREADY_EXIST,
                null,
                $input
            );
        }

        $app = $this->core->update($app, $input);

        return $app->toArrayPublic();
    }

    public function get(string $id): array
    {
        /** @var Entity $app */
        $app = $this->repo->application->findOrFailPublic($id);

        return $app->toArrayPublic();
    }

    private function checkForDuplicate(array $input)
    {
        if (isset($input[Entity::NAME]) === true)
        {
            $app = $this->repo->application->getAppByName($input[Entity::NAME]);

            if (empty($app) === false)
            {
                return true;
            }
        }

        if (isset($input[Entity::TITLE]) === true)
        {
            $app = $this->repo->application->getAppByTitle($input[Entity::TITLE]);

            if (empty($app) === false)
            {
                return true;
            }
        }

        return false;
    }
}
