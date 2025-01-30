import React, { useState, useRef, useMemo } from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { ConditionFact } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition/partials/Fact';
import { ConditionOperator } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition/partials/Operator';
import { RemoveButton } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition/partials/RemoveButton';
import { ConditionValue } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition/partials/Value';
import { ConditionTreeBranch } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition/styled';
import { LIMITS } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/constants';
import {
  useRuleFacts,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import type {
  Condition as TCondition,
  ConditionGroup as TConditionGroup,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type ConditionProps = {
  group: TConditionGroup;
  condition: TCondition;
  indexPosition: number;
  totalConditionGroups: number;
};
export const Condition: React.FC<ConditionProps> = ({
  group,
  condition,
  totalConditionGroups,
  indexPosition,
}) => {
  const valueInputRef = useRef<any>();
  const [isConditionHovered, setIsConditionHovered] = useState(false);
  const { validationResult } = useRuleValidation();
  const { ruleFacts } = useRuleFacts();

  const hasSiblingConditions = group.conditions.length > LIMITS.MIN_CONDITIONS_IN_GROUP;
  const isParentGroupRemovable = totalConditionGroups > LIMITS.MIN_GROUPS_IN_BLOCK;

  const ruleFactUsed = ruleFacts.find((fact) => fact.name === condition.fact);
  const groupErrors = useMemo(
    () => validationResult.conditions[group.id] || {},
    [validationResult.conditions, group],
  );
  const conditionErrors = useMemo(() => groupErrors[condition.id] || {}, [groupErrors, condition]);
  const groupErrorsCount = Object.keys(groupErrors).length;
  const shouldShowRemoveButton =
    isConditionHovered && (hasSiblingConditions || isParentGroupRemovable);

  return (
    <Box
      position="relative"
      display="flex"
      gap="spacing.5"
      flexDirection="column"
      alignItems="stretch"
      onMouseEnter={() => {
        setIsConditionHovered(true);
      }}
      onMouseLeave={() => {
        setIsConditionHovered(false);
      }}
      testID="rcm-condition"
    >
      {shouldShowRemoveButton && (
        <RemoveButton
          condition={condition}
          group={group}
          hasSiblingConditions={hasSiblingConditions}
          isParentGroupRemovable={isParentGroupRemovable}
        />
      )}

      <Box display="flex" gap="spacing.5" alignItems="center" justifyContent="stretch">
        <Box position="relative" width="250px">
          {indexPosition > 0 && (
            <ConditionTreeBranch
              drawVertical={indexPosition > 0 && indexPosition === group.conditions.length - 1}
              errorCount={groupErrorsCount}
              heightMultiplier={indexPosition >= 2 ? indexPosition - 1 : 0}
            />
          )}
          <ConditionFact
            condition={condition}
            conditionErrors={conditionErrors}
            valueInputRef={valueInputRef}
          />
        </Box>
        <Text>op</Text>
        <Box flexGrow="1">
          <ConditionOperator condition={condition} />
        </Box>
      </Box>
      <ConditionValue
        ref={valueInputRef}
        condition={condition}
        conditionErrors={conditionErrors}
        fact={ruleFactUsed}
      />
    </Box>
  );
};
