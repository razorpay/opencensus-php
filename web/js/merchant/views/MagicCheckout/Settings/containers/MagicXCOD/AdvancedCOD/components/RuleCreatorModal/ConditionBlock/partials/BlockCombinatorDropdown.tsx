import React from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import {
  useRule,
  useRuleMutations,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import { TEXT } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionBlock/constants';

export const BlockCombinatorDropdown = () => {
  const { rule } = useRule();
  const mutations = useRuleMutations();

  return (
    <Box position="absolute" left="-15px" top="120px" width="80px">
      <Dropdown selectionType="single">
        <SelectInput
          accessibilityLabel={TEXT.PICK_BLOCK_COMBINATOR}
          value={rule.condition.combinator}
          onChange={({ values }) => {
            mutations.conditions.update('combinator', values[0], rule.condition.path);
          }}
        />
        <DropdownOverlay>
          <ActionList>
            <ActionListItem title="AND" value="and" />
            <ActionListItem title="OR" value="or" />
          </ActionList>
        </DropdownOverlay>
      </Dropdown>
    </Box>
  );
};
