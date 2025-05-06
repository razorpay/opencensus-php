import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const CriticalActionsShimmer = (): JSX.Element => {
  return (
    <Box
      testID="loading-shimmer"
      width="100%"
      display="flex"
      flexDirection="column"
      gap="spacing.5"
    >
      <Skeleton width="240px" height="spacing.8" borderRadius="large" />
      <Box
        display="flex"
        gap={{
          base: 'spacing.0',
          m: 'spacing.5',
        }}
      >
        <Skeleton
          width={{
            base: '100%',
            m: '50%',
            xl: '33%',
          }}
          height={{
            base: '204px',
            m: '176px',
            l: '156px',
          }}
          borderRadius="large"
        />
        <Skeleton
          width={{
            base: '0%',
            m: '50%',
            xl: '33%',
          }}
          height={{
            m: '176px',
            l: '156px',
          }}
          borderRadius="large"
        />
        <Skeleton
          width={{
            base: '0%',
            xl: '33%',
          }}
          height="156px"
          borderRadius="large"
        />
      </Box>
    </Box>
  );
};

export default CriticalActionsShimmer;
