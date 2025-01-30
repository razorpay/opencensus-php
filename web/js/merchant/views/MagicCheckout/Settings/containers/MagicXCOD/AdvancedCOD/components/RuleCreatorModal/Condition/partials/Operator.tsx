import React from 'react';
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
import { operators } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/operators';

import type { Condition as TCondition } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type ConditionOperatorProps = {
  condition: TCondition;
};
export const ConditionOperator: React.FC<ConditionOperatorProps> = ({ condition }) => {
  const { ruleFacts } = useRuleFacts();
  const mutations = useRuleMutations();

  return (
    <Dropdown selectionType="single">
      <SelectInput
        accessibilityLabel="Select operator"
        placeholder="Select operator"
        name="operator"
        defaultValue={condition.operator}
        onChange={({ values }) => {
          mutations.conditions.update('operator', values[0], condition.path);
        }}
      />
      <DropdownOverlay>
        <ActionList>
          {(
            ruleFacts.find((fact) => fact.name === condition.fact)?.operators || [operators.eq]
          ).map((operator) => (
            <ActionListItem key={operator.name} title={operator.label} value={operator.value} />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};
