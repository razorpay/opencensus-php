import * as React from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Button,
  PlusIcon,
} from '@razorpay/blade/components';

import { ConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionGroup';
import { ConditionBlockTreeStem } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/styled';
import {
  useRule,
  useRuleMutations,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import { createDefaultConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/state/defaults';

import type { ConditionGroup as TConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const ConditionBlock = () => {
  const { rule } = useRule();
  const mutations = useRuleMutations();
  const { validationResult } = useRuleValidation();

  const rootConditionBlock = rule.condition;
  const blocks = rootConditionBlock.conditions as TConditionGroup[];
  const blocksLength = blocks.length;
  const lastBlock = blocks[blocksLength - 1] as any;
  const lastBlockErrors = validationResult.conditions[lastBlock.id] || {};
  const root = {
    blocks,
    hasMultipleBlocks: blocksLength > 1,
    lastBlockConditionCount: (lastBlock as TConditionGroup).conditions.length,
    lastBlockErrorsCount: Object.keys(lastBlockErrors).length,
  };

  return (
    <Box paddingLeft={root.hasMultipleBlocks ? '70px' : 'spacing.0'} position="relative">
      <Box>
        {root.blocks.map((block) => {
          return (
            <ConditionGroup key={block.id} block={block} totalConditionGroups={blocksLength} />
          );
        })}

        <Button
          variant="tertiary"
          icon={PlusIcon}
          isFullWidth
          onClick={() => {
            mutations.conditions.add(createDefaultConditionGroup([root.blocks.length]), []);
          }}
        >
          Add another block
        </Button>
        {root.hasMultipleBlocks && (
          <ConditionBlockTreeStem
            lastBlockConditionCount={root.lastBlockConditionCount}
            errorsCount={root.lastBlockErrorsCount}
          />
        )}
      </Box>
      {root.blocks.length > 1 && (
        <Box position="absolute" left="-15px" top="120px" width="80px">
          <Dropdown selectionType="single">
            <SelectInput
              accessibilityLabel="Pick block combinator"
              value={rule.condition.combinator}
              onChange={({ values }) => {
                mutations.conditions.update('combinator', values[0], rule.condition.path as any);
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
  );
};
