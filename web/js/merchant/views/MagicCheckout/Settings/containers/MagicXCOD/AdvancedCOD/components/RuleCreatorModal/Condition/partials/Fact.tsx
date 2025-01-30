import React, { useState, useEffect } from 'react';
import {
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import {
  useRuleFacts,
  useRuleMutations,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import type { Condition as TCondition } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type ConditionFactProps = {
  condition: TCondition;
  conditionErrors: Partial<Record<'fact' | 'operator' | 'value', string | undefined>>;
  valueInputRef: any;
};
export const ConditionFact: React.FC<ConditionFactProps> = ({
  condition,
  conditionErrors,
  valueInputRef,
}) => {
  const [error, setError] = useState<string | undefined>(undefined);
  const { ruleFacts } = useRuleFacts();
  const mutations = useRuleMutations();

  const handleFactChange = (value: string) => {
    const fact = ruleFacts.find((fact) => fact.name === value);
    if (value) {
      setError(undefined);
    }
    mutations.conditions.update('fact', value, condition.path);
    mutations.conditions.update('operator', fact?.defaultOperator?.value, condition.path);
    mutations.conditions.update('value', '', condition.path);
    if (valueInputRef.current) {
      valueInputRef.current.value = '';
    }
  };

  useEffect(() => {
    setError(conditionErrors?.fact);
  }, [conditionErrors]);

  return (
    <Dropdown selectionType="single">
      <SelectInput
        accessibilityLabel="Select condition"
        placeholder="Select condition"
        name="fact"
        validationState={error ? 'error' : 'none'}
        defaultValue={condition.fact}
        onChange={({ values }) => {
          handleFactChange(values[0]);
        }}
      />
      <DropdownOverlay>
        <ActionList>
          {ruleFacts.map((fact) => (
            <ActionListItem key={fact.name} title={fact.label} value={fact.name} />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};
