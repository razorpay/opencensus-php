import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const DeviceCardSkeleton: React.FC = () => {
  return (
    <Box
      padding="spacing.8"
      borderRadius="large"
      display="flex"
      height="22.5rem"
      backgroundColor="surface.background.gray.subtle"
      testID="my-devices-skeleton"
    >
      <Skeleton width="30%" height="100%" borderRadius="large" />
      <Box flex="1" marginLeft="spacing.8" display="flex" flexDirection="column">
        <Skeleton width="60%" height="2.4rem" borderRadius="large" />
        <Skeleton width="40%" height="2rem" borderRadius="large" marginTop="spacing.5" />
        <Skeleton width="100%" height="1.5rem" borderRadius="large" marginTop="auto" />
        <Skeleton
          width="100%"
          height="1.5rem"
          borderRadius="large"
          marginTop="spacing.5"
          marginBottom="spacing.5"
        />
      </Box>
    </Box>
  );
};

export default DeviceCardSkeleton;
