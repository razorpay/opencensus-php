<?php

namespace RZP\Models\Application\ApplicationTags;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Application\ApplicationTags;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new ApplicationTags\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_CREATE_MAPPING, $input);

        $appIdList = $input[Entity::LIST];

        $customInput = [
            Entity::TAG             => strtolower($input[Entity::TAG]),
        ];

        //Check if all appId's are valid
        $appList = $this->repo->application->getAppList($appIdList);

        if (count($appList) !== count($appIdList))
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_APP_DOES_NOT_EXIST,
                null,
                $input
            );
        }

        foreach ($appIdList as $appId)
        {
            $customInput[Entity::APP_ID] = $appId;

            // Do not create duplicate entries of tag -> app_id
            $applicationTagEntity = $this->repo->application_mapping->getAppMapping($customInput[Entity::TAG], $appId);

            if (empty($applicationTagEntity) === true)
            {
                $this->core->create($customInput);
            }
        }

        return [
            $input[Entity::TAG] => "Tag Mapping Created"
        ];
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function deleteAppsInTag(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_DELETE_APP_MAPPING, $input);

        $tag = strtolower($input[Entity::TAG]);

        $list = $input[Entity::LIST];

        if (count($list) === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_EMPTY_DELETE_LIST,
                null,
                $list
            );
        }

        foreach ($list as $deleteAppId)
        {
            $appMapping = $this->repo->application_mapping->getAppMapping($tag, $deleteAppId);

            if (empty($appMapping) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_APP_TAG_MAPPING_DOES_NOT_EXIST,
                    null,
                    [$deleteAppId]
                );
            }
            $this->core->delete($appMapping);
        }

        return [
            "deleted_app" => $list
        ];
    }

    public function deleteTag(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_DELETE_TAG, $input);

        $tag = strtolower($input[Entity::TAG]);

        $merchantTagList = $this->repo->application_merchant_tag->getTagUsage($tag);

        if (count($merchantTagList) > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MERCHANT_TAG_IN_USE,
                null,
                $tag
            );
        }

        $appMappings = $this->repo->application_mapping->getAppMappingByTag($tag);

        if (count($appMappings) === 0)
        {
            return [
                $tag => "tag not found"
            ];
        }

        foreach ($appMappings as $appMapping)
        {
            $this->core->delete($appMapping);
        }

        return [
            $tag => "tag mapping deleted"
        ];
    }
}
