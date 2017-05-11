<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Entity as E;

class Validator extends Base\Validator
{
    protected static $entityFetchRules = [
        'entity_id'     => 'required|alpha_num|max:14',
        'entity'        => 'required|string'
    ];

    /**
     * Entities for which file_store entity exists
     * Used to validate input from 'file_get_signed_url'
     *
     */
    protected $allowed = [
        E::REPORT,
    ];

    /**
     * Throws Exception if entity is not allowed
     *
     * @param string $entity
     *
     * @return void
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateRequestEntity(string $entity)
    {
        if (in_array($entity, $this->allowed, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get file for the given entity type');
        }
    }
}
