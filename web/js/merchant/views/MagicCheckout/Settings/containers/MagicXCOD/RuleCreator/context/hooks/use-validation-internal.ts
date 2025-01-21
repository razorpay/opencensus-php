import { useState } from 'react';

import {
  Rule,
  RuleValidationResult,
  UseValidationInternal,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

const defaultValidationResult = {
  conditions: {},
  actions: {},
};

export const useValidationInternal: UseValidationInternal = (props) => {
  const { validator } = props;
  const [validationResult, setValidationResult] =
    useState<RuleValidationResult>(defaultValidationResult);

  const validateRule = (rule: Rule) => {
    const errors = validator(rule);
    const isValid =
      Object.keys(errors.conditions).length === 0 && Object.keys(errors.actions).length === 0;
    setValidationResult(errors);

    return isValid;
  };

  return [validationResult, validateRule];
};
