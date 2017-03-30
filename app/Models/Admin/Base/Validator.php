<?php

namespace RZP\Models\Admin\Base;

use Validator as LaravelValidator;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Admin\Org\FieldMap;

class Validator extends Base\Validator
{
    public $isOrgSpecificValidationSupported = false;

    public function validateOrgSpecificInput(
        string $operation,
        array $input,
        string $orgId,
        string $entity = null)
    {
        $rules = $this->getRulesForOrg($operation, $orgId, $entity);

        // We check for valid keys because single entity stores unique
        // fields for many orgs which should not be errorneously filled.
        $invalidKeys = array_keys(array_diff_key($input, $rules));

        if (count($invalidKeys) > 0)
        {
            $this->throwExtraFieldsException($invalidKeys);
        }

        $this->validateInputValuesForOrg(
            $operation, $input, $orgId, $rules, $entity);

        $this->runValidators($operation, $input);
    }

    protected function getRulesForOrg(
        string $operation,
        string $orgId,
        string $entity = null)
    {
        // Validator may or may not have entity passed to it
        if (empty($entity) === true)
        {
            if (empty($this->entity) === false)
            {
                $entity = $this->entity->getEntityName();
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The entity is not given in input or as member variable of validator class');
            }
        }

        $rulesVar = $this->getRulesVariableName($operation);

        $fieldMap = (new FieldMap\Repository)->findByOrgIdAndEntity(
            $orgId, $entity);

        // If the org-specific rules for a org are not defined,
        // return all the rules and let it consider all the entries
        // in the rules as applicable which is basically default validator
        if ($fieldMap === null)
        {
            return static::$$rulesVar;
        }

        $fields = $fieldMap->getFields();

        return array_intersect_key(static::$$rulesVar, array_flip($fields));
    }

    protected function validateInputValuesForOrg(
        string $operation,
        array $input,
        string $orgCode,
        array $rules,
        string $entity = null)
    {
        $customAttributes = $this->getCustomAttributes($operation);

        $validator = LaravelValidator::make(
                        $input,
                        $rules,
                        array(),
                        $customAttributes);

        $this->laravelValidatorInstance = $validator;

        $validator->setEntityValidator($this);

        if ($validator->fails())
        {
            $this->processValidationFailure(
                $validator->messages(), $operation, $input);
        }

        return $this;
    }

    public function getRulesForOperation(string $operation)
    {
        $rulesVar = $this->getRulesVariableName($operation);

        return static::$$rulesVar;
    }
}
