import React from 'react';
import { AlertTriangleIcon, Box, BoxProps, Link, Text } from '@razorpay/blade/components';

import { AlertIconWrapper } from 'merchant/widgets/common/styled';
import { ErrorStateProps } from 'merchant/widgets/common/types';
import { track } from 'merchant/widgets/utils';

export const ErrorState = ({
  text,
  retryHandler,
  analyticsProperties,
  ...rest
}: ErrorStateProps & BoxProps): JSX.Element => {
  const tryAgainHandler = () => {
    if (analyticsProperties) {
      const { screen, ...restAnalyticsProperties } = analyticsProperties;
      track({
        objectName: 'widget retry',
        actionName: 'clicked',
        screen,
        properties: restAnalyticsProperties,
      });
    }
    retryHandler?.();
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      height="175px"
      borderRadius="medium"
      marginX={{ s: 'spacing.0', m: 'spacing.6' }}
      {...rest}
    >
      <AlertIconWrapper variant="error">
        <AlertTriangleIcon color="feedback.icon.negative.intense" />
      </AlertIconWrapper>
      <Text weight="semibold" marginTop="spacing.4">
        {text || 'Something went wrong'}
      </Text>
      {retryHandler ? (
        <Link onClick={tryAgainHandler} variant="button" marginTop="spacing.4">
          Try Again
        </Link>
      ) : null}
    </Box>
  );
};
