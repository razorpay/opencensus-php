<?php

namespace RZP\Models\Admin\Base;

use RZP\Base;
use Validator as LaravelValidator;

class Validator extends Base\Validator
{
    public function validateOrgSpecificInput(
        string $operation,
        array $input,
        string $orgCode)
    {
        $rulesVar = $this->getRulesVariableForOrg($operation, $orgCode);

        // We check for valid keys  because single entity stores unique fields for many
        // orgs which should not be erroneously filled.
        $invalidKeys = array_keys(array_diff_key($input, static::$$rulesVar));

        if (count($invalidKeys) > 0)
        {
            $this->throwExtraFieldsException($invalidKeys);
        }

        $this->validateInputValuesForOrg($operation, $input, $orgCode);
    }

    protected function getRulesVariableForOrg(
        string $operation,
        string $orgCode)
    {
        $rulesVar = $this->getRulesVariableName($operation);

        return lcfirst($rulesVar . 'For' . studly_case($orgCode));
    }

    protected function validateInputValuesForOrg(
        string $operation,
        array $input,
        string $orgCode)
    {
        $rulesVar = $this->getRulesVariableForOrg($operation);

        $customAttributes = $this->getCustomAttributes($operation);

        $validator = LaravelValidator::make(
                        $input,
                        static::$$rulesVar,
                        array(),
                        $customAttributes);

        $this->laravelValidatorInstance = $validator;

        $validator->setEntityValidator($this);

        if ($validator->fails())
        {
            $this->processValidationFailure($validator->messages(), $operation, $input);
        }

        return $this;
    }
}
