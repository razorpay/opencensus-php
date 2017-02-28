<?php

namespace RZP\Models\Admin\Base;

use Validator as LaravelValidator;

use RZP\Base;
use RZP\Models\Admin\Org\FieldMap;

class Validator extends Base\Validator
{
    public function validateOrgSpecificInput(
        string $operation,
        array $input,
        string $orgId)
    {
        $rules = $this->getRulesVariableForOrg($operation, $orgId);

        // We check for valid keys because single entity stores unique
        // fields for many orgs which should not be errorneously filled.
        $invalidKeys = array_keys(array_diff_key($input, $rules));

        if (count($invalidKeys) > 0)
        {
            $this->throwExtraFieldsException($invalidKeys);
        }

        $this->validateInputValuesForOrg($operation, $input, $orgId);
    }

    protected function getRulesVariableForOrg(
        string $operation,
        string $orgId)
    {
        $entity = 'admin_lead';

        $rulesVar = $this->getRulesVariableName($operation);

        $fields = (new FieldMap\Repository)
                    ->findByOrgIdAndEntity($orgId, $entity)
                    ->getFields();

        return array_intersect_key(static::$$rulesVar, array_flip($fields));
    }

    protected function validateInputValuesForOrg(
        string $operation,
        array $input,
        string $orgCode)
    {
        $rules = $this->getRulesVariableForOrg($operation, $orgCode);

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
