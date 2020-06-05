<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Base;
use RZP\Constants\Entity as E;

class Validator extends Base\Validator
{
    /**
     * Entities for which file_store entity exists
     * Used to validate input from 'file_get_signed_url'
     *
     */
    protected $allowed = [
        E::REPORT,
        E::COMMISSION_INVOICE,
    ];

    /*
     * entity_id => can be a pulic id, hence 20 chars
     * entity    => should be one of the allowed entities
     */
    protected static $entityFetchRules = [
        'entity_id'     => 'required|alpha_dash|max:20',
        'entity'        => 'required|string|max:20|custom'
    ];

    /**
     * Throws Exception if entity is not allowed
     *
     * @param string $entity
     *
     * @return void
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateEntity($attribute, $value)
    {
        if (in_array($value, $this->allowed, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get file for the given entity type');
        }
    }
}
