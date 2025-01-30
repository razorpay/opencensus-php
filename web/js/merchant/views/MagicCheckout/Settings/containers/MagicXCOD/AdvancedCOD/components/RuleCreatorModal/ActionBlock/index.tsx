import * as React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { ActionType } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/partials/ActionType';
import { PaymentParams } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/partials/PaymentParams';
import { ShippingParams } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/partials/ShippingParams';
import {
  useRule,
  useRuleValidation,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import {
  TEXT,
  ACTION_INDEX,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/constants';

type ActionBlockProps = {
  type: 'shipping' | 'payment';
  shippingProfiles: any;
};
export const ActionBlock: React.FC<ActionBlockProps> = ({ type, shippingProfiles }) => {
  const { rule } = useRule();
  const { validationResult } = useRuleValidation();
  const ruleActionType = rule.actions[ACTION_INDEX].type;
  const validation = validationResult.actions[ACTION_INDEX];

  const shouldShowParamsSelect = /show_specific|hide_specific/.test(ruleActionType.toLowerCase());

  return (
    <Box testID="rcm-actionblock">
      <Box
        backgroundColor="surface.background.gray.moderate"
        borderColor="surface.border.gray.muted"
        padding="spacing.6"
        display="flex"
        flexDirection="column"
        gap="spacing.6"
        borderRadius="large"
      >
        <Box display="flex">
          <Box width="102px">
            <Box paddingX="spacing.4" paddingY="spacing.3">
              <Text size="medium" weight="semibold">
                {TEXT.THEN}
              </Text>
            </Box>
          </Box>
          <Box flexGrow="1" display="flex" flexDirection="column" gap="spacing.7">
            <Box display="flex" gap="spacing.5" flexDirection="column" alignItems="stretch">
              <ActionType
                type={type}
                validationError={validation?.type}
                defaultValue={ruleActionType}
              />
              {shouldShowParamsSelect &&
                (type === 'shipping' ? (
                  <ShippingParams
                    validationError={validation?.params}
                    defaultValue={rule.actions[ACTION_INDEX].params?.value}
                    shippingProfiles={shippingProfiles}
                  />
                ) : (
                  <PaymentParams
                    validationError={validation?.params}
                    defaultValue={rule.actions[ACTION_INDEX].params?.value?.join(',') || ''}
                  />
                ))}
            </Box>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
