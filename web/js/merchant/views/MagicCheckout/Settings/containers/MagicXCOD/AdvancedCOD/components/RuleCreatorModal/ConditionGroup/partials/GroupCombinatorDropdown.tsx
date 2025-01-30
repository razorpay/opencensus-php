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

import { TEXT } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionGroup/constants';

import type { ConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type Props = {
  group: ConditionGroup;
  isFirstConditionErrored: boolean;
};
export const GroupCombinatorDropdown: React.FC<Props> = ({ group, isFirstConditionErrored }) => {
  const mutations = useRuleMutations();

  return (
    <Box
      position="absolute"
      marginTop={isFirstConditionErrored ? '94px' : '76px'}
      marginLeft="-8px"
      width="80px"
    >
      <Dropdown selectionType="single">
        <SelectInput
          accessibilityLabel={TEXT.PICK_GROUP_COMBINATOR}
          value={group.combinator}
          onChange={({ values }) => {
            mutations.conditions.update('combinator', values[0], group.path);
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
