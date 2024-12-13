import { getOperatorsForFactType } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/operators';
import { parseFacts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/parse';
import { stringValidator } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/validators';

import type { Fact } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

describe('parseFacts', () => {
  it('should parse and enhance API returned rulefacts', () => {
    const initialFacts: Fact[] = [
      { type: 'string', name: 'customerEmail', label: 'Customer email' },
    ];
    const operators = getOperatorsForFactType('string');
    const parsedFacts = [
      {
        type: 'string',
        name: 'customerEmail',
        label: 'Customer email',
        operators,
        defaultOperator: operators[0],
        validator: stringValidator,
      },
    ];

    expect(parseFacts(initialFacts)).toStrictEqual(parsedFacts);
  });
});
