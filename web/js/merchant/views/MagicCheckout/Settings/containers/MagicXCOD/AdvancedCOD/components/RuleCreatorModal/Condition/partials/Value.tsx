import React, { forwardRef } from 'react';
import {
  Box,
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import debounce from 'common/utils/debounce';
import { getTextInputPropsForValue } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition/helpers';
import { booleanSelectInputOptions } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';
import { useRuleMutations } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import type {
  Condition as TCondition,
  Fact,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type ConditionValueProps = {
  condition: TCondition;
  fact: Fact | undefined;
  conditionErrors: Partial<Record<'fact' | 'operator' | 'value', string | undefined>>;
};
export const ConditionValue = forwardRef<any, ConditionValueProps>(
  ({ condition, fact, conditionErrors }, ref) => {
    const mutations = useRuleMutations();

    const handleValueChange = debounce((value: any) => {
      let formattedValue = value;
      if (fact?.type === 'number') {
        try {
          formattedValue = parseFloat(value);
        } catch (_) {
          //
        }
      }
      mutations.conditions.update('value', formattedValue, condition.path);
    }, 800);

    return (
      <Box>
        {fact?.type !== 'boolean' ? (
          <TextInput
            ref={ref}
            label=""
            {...getTextInputPropsForValue(condition, fact)}
            errorText={conditionErrors.value || ''}
            validationState={conditionErrors.value ? 'error' : 'none'}
            defaultValue={String(condition.value)}
            onChange={({ value }) => {
              handleValueChange(value);
            }}
          />
        ) : (
          <Dropdown selectionType="single">
            <SelectInput
              accessibilityLabel="Select value"
              placeholder="Select value"
              defaultValue={String(condition.value)}
              validationState={conditionErrors.value ? 'error' : 'none'}
              onChange={({ values }) => {
                handleValueChange(values[0] === 'true');
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {booleanSelectInputOptions.map((option) => (
                  <ActionListItem key={option.name} title={option.label} value={option.value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        )}
      </Box>
    );
  },
);
