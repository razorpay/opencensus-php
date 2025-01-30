import React from 'react';
import { Box, Button, CloseIcon } from '@razorpay/blade/components';

import { useRuleMutations } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import type {
  Condition as TCondition,
  ConditionGroup as TConditionGroup,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type RemoveButtonProps = {
  condition: TCondition;
  group: TConditionGroup;
  hasSiblingConditions: boolean;
  isParentGroupRemovable: boolean;
};
export const RemoveButton: React.FC<RemoveButtonProps> = ({
  condition,
  group,
  hasSiblingConditions,
  isParentGroupRemovable,
}) => {
  const mutations = useRuleMutations();

  return (
    <Box
      position="absolute"
      top="spacing.0"
      right="100%"
      bottom="spacing.0"
      left="-50px"
      zIndex={10}
    >
      <Box position="absolute" bottom="spacing.0">
        <Button
          accessibilityLabel="Remove condition"
          variant="tertiary"
          icon={CloseIcon}
          onClick={() => {
            if (hasSiblingConditions) {
              mutations.conditions.remove(condition.path);
            }

            if (!hasSiblingConditions && isParentGroupRemovable) {
              mutations.conditions.remove(group.path);
            }
          }}
        />
      </Box>
    </Box>
  );
};
