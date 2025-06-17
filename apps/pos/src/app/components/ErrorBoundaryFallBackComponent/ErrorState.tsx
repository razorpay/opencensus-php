import React from 'react';
import { AlertTriangleIcon, Box, BoxProps, Link, Text } from '@razorpay/blade/components';
import { AlertIconWrapper } from './styled';
import { analyticsTrack } from '@libs/shared-utils';

export interface ErrorStateProps {
  text?: string;
  retryHandler?: () => void;
  analyticsProperties?: {
    screen?: string;
    [key: string]: any;
  };
}

export const ErrorState = ({
  text,
  retryHandler,
  analyticsProperties,
  ...rest
}: ErrorStateProps & BoxProps): JSX.Element => {
  const tryAgainHandler = () => {
    if (analyticsProperties) {
      const { screen, ...restAnalyticsProperties } = analyticsProperties;
      analyticsTrack({
        objectName: 'widget retry',
        actionName: 'clicked',
        screen: screen ?? '',
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
