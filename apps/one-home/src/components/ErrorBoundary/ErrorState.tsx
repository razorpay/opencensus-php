import React from 'react';
import { Box, Heading, AlertTriangleIcon, Text } from '@razorpay/blade/components';
import { ErrorStateProps } from './types';
import { staticText } from './constants';

const ErrorState = ({
  title = staticText.defaultText,
  padding = 'spacing.8',
  onRetry,
  size = 'large',
  borderRadius = 'none',
  withBorder = false,
}: ErrorStateProps) => {
  const BorderStyle = withBorder
    ? ({
        borderWidth: 'thinner',
        borderColor: 'surface.border.gray.muted',
      } as const)
    : {};

  return (
    <Box
      padding={padding}
      display="flex"
      gap="spacing.3"
      alignItems="center"
      flexDirection="column"
      flexGrow={1}
      borderRadius={borderRadius}
      backgroundColor="surface.background.gray.intense"
      justifyContent="center"
      {...BorderStyle}
    >
      <AlertTriangleIcon size={size} color="feedback.icon.notice.intense" />
      <Text
        size={'medium'}
        variant="body"
        weight="semibold"
        color="surface.text.gray.normal"
        textAlign="center"
      >
        {title}
      </Text>
      {/* <Link variant="button" color="primary" size="large" onClick={onRetry}>
        Retry now
      </Link> */}
    </Box>
  );
};

export default ErrorState;
