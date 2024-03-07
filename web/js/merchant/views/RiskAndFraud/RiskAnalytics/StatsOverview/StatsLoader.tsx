import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const StatsLoader = (): JSX.Element => {
  return (
    <Box
      testID="loading-shimmer"
      display="flex"
      flexDirection="row"
      flex="1"
      paddingRight="spacing.7"
    >
      <Box display="flex" flexDirection="column" flex="1" paddingTop="spacing.3">
        <Skeleton width="160px" height="22px" marginBottom="spacing.3" />
        <Skeleton width="80px" height="36px" />
      </Box>
    </Box>
  );
};

export default StatsLoader;
