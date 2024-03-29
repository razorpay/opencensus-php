import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const TabSkeleton = ({ isActive = false }: { isActive?: boolean }): JSX.Element => {
  return (
    <Box
      display="flex"
      flex={1}
      flexDirection="column"
      padding={['spacing.5', 'spacing.7']}
      backgroundColor={
        isActive ? 'surface.background.gray.intense' : 'surface.background.gray.moderate'
      }
      borderTopWidth="none"
      borderColor="surface.border.gray.muted"
      borderLeftWidth={isActive ? 'none' : 'thinner'}
      borderRightWidth={isActive ? 'none' : 'thinner'}
      borderBottomWidth="thicker"
      borderBottomColor={
        isActive ? 'surface.border.primary.normal' : 'surface.border.primary.muted'
      }
      testID="tab-skeleton"
    >
      <Skeleton width="160px" height="15px" borderRadius="large" />
      <Skeleton width="110px" height="32px" borderRadius="max" marginTop="spacing.4" />
      <Skeleton width="110px" height="16px" borderRadius="large" marginTop="spacing.5" />
    </Box>
  );
};

const LoadingSkeleton = ({ count }: { count: number | undefined }): JSX.Element => {
  return (
    <Box display="flex" flexDirection="column" testID="loading-skeleton">
      <Box display="flex" flexDirection="row">
        {Array.from({ length: count || 3 }, (v, k) => (
          <TabSkeleton key={k} isActive={k === 0} />
        ))}
      </Box>
      <Skeleton
        height="220px"
        borderRadius="large"
        margin={['spacing.5', 'spacing.7', 'spacing.0', 'spacing.7']}
      />
    </Box>
  );
};

export default LoadingSkeleton;
