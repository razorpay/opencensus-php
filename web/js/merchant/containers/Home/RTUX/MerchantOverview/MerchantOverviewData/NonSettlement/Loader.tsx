import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const NonSettlementLoader = ({ isRiskFohMerchant = false }) => {
  return (
    <Box
      display="flex"
      gap="spacing.7"
      flexDirection={{ base: 'column', l: 'row' }}
      justifyContent={{ base: 'center', l: 'unset' }}
      alignItems={{ base: 'center', l: 'unset' }}
      width="100%"
      testID="non-settlement-shimmer"
      padding={isRiskFohMerchant ? 'spacing.6' : 'spacing.0'}
    >
      <Skeleton height="100px" width="100px" />
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        gap="spacing.5"
        width="100%"
      >
        <Box textAlign={{ base: 'center', l: 'unset' }}>
          <Skeleton width="50%" height="20px" borderRadius="medium" marginBottom="spacing.2" />
          <Skeleton width="80%" height="24px" borderRadius="medium" />
        </Box>
        <Box display="flex" gap="spacing.5" flexDirection={{ base: 'column', l: 'row' }}>
          <Skeleton width="150px" height="24px" borderRadius="medium" />
          <Skeleton width="150px" height="24px" borderRadius="medium" />
        </Box>
      </Box>
    </Box>
  );
};

export default NonSettlementLoader;
