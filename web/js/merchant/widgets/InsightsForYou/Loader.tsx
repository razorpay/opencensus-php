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
        padding="spacing.5"
      >
        <Skeleton width="50%" height="32px" borderRadius="max" />
        <Skeleton width="80%" height="20px" borderRadius="max" />
        <Skeleton width="20%" height="20px" borderRadius="max" />
      </Box>
    </Box>
  );
};
