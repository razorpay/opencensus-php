import { getOperatorsForFactType } from './operators';
import { validators } from './validators';

import type { Fact } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const parseFacts = (facts: Array<Fact>): Array<Fact> => {
  return facts.map((fact) => {
    const { defaultOperator, validator } = fact;
    const operators = getOperatorsForFactType(fact.type);

    return {
      ...fact,
      operators,
      defaultOperator: defaultOperator || operators[0],
      validator: validator || validators[fact.type],
    };
  });
};
