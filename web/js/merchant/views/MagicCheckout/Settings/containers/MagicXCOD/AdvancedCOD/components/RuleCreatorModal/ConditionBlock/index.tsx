import * as React from 'react';
import { Box, Button, PlusIcon } from '@razorpay/blade/components';

import { BlockCombinatorDropdown } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionBlock/partials/BlockCombinatorDropdown';
import { ConditionBlockTreeStem } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionBlock/styled';
import { ConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionGroup';
import {
  useRule,
  useRuleMutations,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import { createDefaultConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/state/defaults';

import { TEXT } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionBlock/constants';

import type { ConditionGroup as TConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const ConditionBlock = () => {
  const { rule } = useRule();
  const mutations = useRuleMutations();
  const { validationResult } = useRuleValidation();

  const rootConditionBlock = rule.condition;
  const groups = rootConditionBlock.conditions as TConditionGroup[];
  const groupsLength = groups.length;
  const lastGroup = groups[groupsLength - 1];
  const lastGroupErrors = validationResult.conditions[lastGroup.id] || {};
  const meta = {
    groups,
    hasMultipleGroups: groupsLength > 1,
    lastGroupConditionCount: (lastGroup as TConditionGroup).conditions.length,
    lastGroupErrorsCount: Object.keys(lastGroupErrors).length,
  };

  return (
    <Box
      paddingLeft={meta.hasMultipleGroups ? '70px' : 'spacing.0'}
      position="relative"
      testID="rcm-conditionblock"
    >
      <Box>
        {meta.groups.map((group) => {
          return (
            <ConditionGroup key={group.id} group={group} totalConditionGroups={groupsLength} />
          );
        })}

        <Button
          variant="tertiary"
          icon={PlusIcon}
          isFullWidth
          onClick={() => {
            mutations.conditions.add(createDefaultConditionGroup([meta.groups.length]), []);
          }}
        >
          {TEXT.ADD_ANOTHER_BLOCK}
        </Button>
        {meta.hasMultipleGroups && (
          <ConditionBlockTreeStem
            lastBlockConditionCount={meta.lastGroupConditionCount}
            errorsCount={meta.lastGroupErrorsCount}
          />
        )}
      </Box>
      {meta.groups.length > 1 && <BlockCombinatorDropdown />}
    </Box>
  );
};
