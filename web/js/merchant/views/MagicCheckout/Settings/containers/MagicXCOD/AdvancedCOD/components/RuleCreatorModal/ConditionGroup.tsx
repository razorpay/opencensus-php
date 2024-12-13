import * as React from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Button,
  PlusIcon,
} from '@razorpay/blade/components';

import { Condition } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition';
import { ConditionBlockTreeBranch } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/styled';
import {
  useRuleMutations,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import { newCondition, LIMITS } from './constants';

import type { Condition as TCondition } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type ConditionGroupProps = {
  block: any;
  totalConditionGroups: number;
};
export const ConditionGroup: React.FC<ConditionGroupProps> = ({ block, totalConditionGroups }) => {
  const mutations = useRuleMutations();
  const { validationResult } = useRuleValidation();
  const groupErrors = validationResult.conditions[block.id] || {};
  const firstConditionErrors = groupErrors[block.conditions[0].id];
  const isFirstConditionErrored = firstConditionErrors?.value;
  const shouldRenderBranch = totalConditionGroups > LIMITS.MIN_GROUPS_IN_BLOCK;

  return (
    <Box position="relative" key={block.id}>
      {shouldRenderBranch && <ConditionBlockTreeBranch />}
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
            {block.conditions.map((condition, idx) => (
              <Condition
                key={condition.id}
                indexPosition={idx}
                condition={condition as TCondition}
                block={block}
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
                  mutations.conditions.add({ ...newCondition }, block.path);
                }}
              >
                Add another condition
              </Button>
            </Box>
          </Box>
          <Box width="102px" position="relative">
            <Box paddingX="spacing.4" paddingY="spacing.3" position="relative">
              <Text size="medium" weight="semibold">
                When
              </Text>
            </Box>
            {block.conditions.length > 1 && (
              <Box
                position="absolute"
                marginTop={isFirstConditionErrored ? '94px' : '76px'}
                marginLeft="-8px"
                width="80px"
              >
                <Dropdown selectionType="single">
                  <SelectInput
                    accessibilityLabel=""
                    value={block.combinator}
                    onChange={({ values }) => {
                      mutations.conditions.update('combinator', values[0], block.path);
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
            )}
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
