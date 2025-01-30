import * as React from 'react';
import { Box, Text, Button, PlusIcon } from '@razorpay/blade/components';

import { Condition } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition';
import { ConditionBlockTreeBranch } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionBlock/styled';
import { GroupCombinatorDropdown } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionGroup/partials/GroupCombinatorDropdown';
import {
  useRuleMutations,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import { TEXT } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionGroup/constants';
import {
  newCondition,
  LIMITS,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';

import type {
  Condition as TCondition,
  ConditionGroup as TConditionGroup,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type ConditionGroupProps = {
  group: TConditionGroup;
  totalConditionGroups: number;
};
export const ConditionGroup: React.FC<ConditionGroupProps> = ({ group, totalConditionGroups }) => {
  const mutations = useRuleMutations();
  const { validationResult } = useRuleValidation();

  const groupErrors = validationResult.conditions[group.id] || {};
  const firstConditionErrors = groupErrors[group.conditions[0].id];
  const isFirstConditionErrored = Boolean(firstConditionErrors?.value);
  const shouldRenderBlockTreeBranch = totalConditionGroups > LIMITS.MIN_GROUPS_IN_BLOCK;

  return (
    <Box position="relative" key={group.id} testID="rcm-conditiongroup">
      {shouldRenderBlockTreeBranch && <ConditionBlockTreeBranch />}
      <Box
        backgroundColor="surface.background.gray.moderate"
        borderColor="surface.border.gray.muted"
        padding="spacing.6"
        display="flex"
        flexDirection="column"
        gap="spacing.6"
        borderRadius="medium"
        marginBottom="spacing.6"
      >
        <Box display="flex" flexDirection="row-reverse">
          <Box flexGrow="1" display="flex" flexDirection="column" gap="spacing.7">
            {group.conditions.map((condition, idx) => (
              <Condition
                key={condition.id}
                indexPosition={idx}
                condition={condition as TCondition}
                group={group}
                totalConditionGroups={totalConditionGroups}
              />
            ))}
            <Box maxWidth="fit-content">
              <Button
                variant="tertiary"
                size="small"
                role="button"
                icon={PlusIcon}
                onClick={() => {
                  mutations.conditions.add({ ...newCondition }, group.path);
                }}
              >
                {TEXT.ADD_ANOTHER_CONDITION}
              </Button>
            </Box>
          </Box>
          <Box width="102px" position="relative">
            <Box paddingX="spacing.4" paddingY="spacing.3" position="relative">
              <Text size="medium" weight="semibold">
                {TEXT.WHEN}
              </Text>
            </Box>
            {group.conditions.length > 1 && (
              <GroupCombinatorDropdown
                group={group}
                isFirstConditionErrored={isFirstConditionErrored}
              />
            )}
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
