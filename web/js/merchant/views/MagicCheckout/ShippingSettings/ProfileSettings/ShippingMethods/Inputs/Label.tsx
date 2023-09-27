import { Box, Text } from '@razorpay/blade/components';
import React from 'react';

const Label = ({
  value,
  error,
  required = true,
}: {
  value: string;
  error?: string;
  required?: boolean;
}): JSX.Element => {
  const hasError = error && error?.length > 0;
  return (
    <Box width="120px">
      <Text
        color={
          hasError ? 'feedback.text.negative.lowContrast' : 'feedback.text.neutral.lowContrast'
        }
        weight="bold"
      >
        {value}
        {required ? (
          <Text as="span" color="feedback.text.negative.lowContrast">
            *
          </Text>
        ) : null}
      </Text>
      {hasError ? (
        <Text size="small" color="feedback.text.negative.lowContrast">
          {error}
        </Text>
      ) : null}
    </Box>
  );
};

export default Label;
