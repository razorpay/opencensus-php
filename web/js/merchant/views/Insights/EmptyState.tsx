import React from 'react';
import { AlertTriangleIcon, Box, Heading, Text, useTheme, Link } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import { AlertIconWrapper } from 'merchant/widgets/common/styled';
import { ErrorStateProps } from 'merchant/widgets/common/types';
import { track } from 'merchant/widgets/utils';

const EmptyState = ({ text, retryHandler, analyticsProperties }: ErrorStateProps): JSX.Element => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isDesktop = matchedDeviceType === 'desktop';
  const tryAgainHandler = () => {
    if (analyticsProperties) {
      const { screen, ...restAnalyticsProperties } = analyticsProperties;
      track({
        objectName: `Insights - ${text} EmptyState`,
        actionName: 'clicked',
        screen: `${text}`,
        properties: restAnalyticsProperties,
      });
    }
    retryHandler?.();
  };
  return (
    <Box
      padding="spacing.7"
      height={isDesktop ? '400px' : '200px'}
      marginX={isDesktop ? 'spacing.6' : 'spacing.4'}
      backgroundColor="surface.background.gray.intense"
      display="flex"
      marginTop="spacing.7"
      justifyContent="center"
    >
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        alignItems="center"
        justifyContent="center"
        padding={!isDesktop ? 'spacing.2' : 'spacing.7'}
        marginX={isDesktop ? 'spacing.10' : 'none'}
      >
        <AlertIconWrapper variant="error">
          <AlertTriangleIcon
            size={!isDesktop ? 'medium' : '2xlarge'}
            color="feedback.icon.negative.intense"
          />
        </AlertIconWrapper>
        <Heading
          textAlign="center"
          marginTop="spacing.7"
        >{`No data available for ${text} at the moment!`}</Heading>
        <Text weight="medium">To be populated soon 🎉</Text>
        {retryHandler ? (
          <Link onClick={tryAgainHandler} variant="button" size="medium">
            Try Again
          </Link>
        ) : null}
      </Box>
    </Box>
  );
};

export { EmptyState };
