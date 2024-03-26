import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { Arrow } from './Arrow';
import { ChangeProps } from './types';
import { COLORS } from 'merchant/containers/Home/RTUX/colors';

const colors = {
  increase: COLORS.green,
  decrease: COLORS.red,
};

// If inverted arrow direction is unchanged, but color changes
export const Change: React.FC<ChangeProps> = ({
  variant,
  text,
  isInverted,
}): JSX.Element | null => {
  const finalVariant = isInverted ? (variant === 'increase' ? 'decrease' : 'increase') : variant;
  return (
    <Box display="flex" alignItems="center" gap="spacing.2" testID="change-component">
      <Arrow variant={variant} fill={colors[finalVariant]} />
      <Text
        weight="bold"
        color={
          finalVariant === 'increase'
            ? 'feedback.positive.action.text.link.default.lowContrast'
            : 'feedback.negative.action.text.link.default.lowContrast'
        }
      >
        {text}
      </Text>
    </Box>
  );
};
