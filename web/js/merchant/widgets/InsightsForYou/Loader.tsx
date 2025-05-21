import { Box, Skeleton } from '@razorpay/blade/components';
import React from 'react';

export const InsightsForYouWidgetLoader = () => {
  return (
    <Box testID="insights-for-you-widget-loader">
      <Box height={'auto'} backgroundColor="surface.background.gray.moderate" flex="1" />
      <Box
        width={'65%'}
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        gap="spacing.3"
        padding="spacing.3"
        marginX={{ base: 'spacing.2', m: 'spacing.4' }}
      >
        <Skeleton width="50%" height="spacing.5" borderRadius="max" />
      </Box>
    </Box>
  );
};
