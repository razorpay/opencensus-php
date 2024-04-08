import React from 'react';
import { Box, Text, useTheme } from '@razorpay/blade/components';

import { Arrow } from './Arrow';
import { ChangeProps } from './types';

// If inverted arrow direction is unchanged, but color changes
export const Change: React.FC<ChangeProps> = ({
  variant,
  text,
  isInverted,
}): JSX.Element | null => {
  const { theme } = useTheme();
  const finalVariant = isInverted ? (variant === 'increase' ? 'decrease' : 'increase') : variant;
  return (
    <Box display="flex" alignItems="center" gap="spacing.2" testID="change-component">
      <Arrow
        variant={variant}
        fill={
          finalVariant === 'increase'
            ? theme.colors.interactive.text.positive.normal
            : theme.colors.interactive.text.negative.normal
        }
      />
      <Text
        weight="semibold"
        color={
          finalVariant === 'increase'
            ? 'interactive.text.positive.normal'
            : 'interactive.text.negative.normal'
        }
      >
        {text}
      </Text>
    </Box>
  );
};
