import React from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { useRuleMutations } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import { actionTypes } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';
import {
  TEXT,
  ACTION_INDEX,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/constants';

import type { RuleType as ACODRuleType } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type ActionTypeProps = {
  validationError: string | undefined;
  type: ACODRuleType;
  defaultValue: string;
};
export const ActionType: React.FC<ActionTypeProps> = ({ type, validationError, defaultValue }) => {
  const mutations = useRuleMutations();

  const handleTypeSelect = (type: string) => {
    mutations.actions.update('type', type, ACTION_INDEX);

    if (!/hide_specific|show_specific/.test(type.toLowerCase())) {
      mutations.actions.update('params', {}, ACTION_INDEX);
    } else {
      mutations.actions.update('params', { value: [] }, ACTION_INDEX);
    }
  };

  return (
    <Box display="flex" gap="spacing.5" alignItems="center">
      <Box flexGrow="1">
        <Dropdown selectionType="single">
          <SelectInput
            accessibilityLabel={TEXT.ACTION_TYPE.PLACEHOLDER}
            placeholder={TEXT.ACTION_TYPE.A11Y_LABEL}
            errorText={validationError}
            validationState={validationError ? 'error' : 'none'}
            name="type"
            defaultValue={defaultValue}
            onChange={({ values }) => {
              handleTypeSelect(values[0]);
            }}
          />
          <DropdownOverlay>
            <ActionList>
              {actionTypes[type].map((action) => (
                <ActionListItem key={action.type} title={action.label} value={action.type} />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
    </Box>
  );
};
