import { Box } from '@razorpay/blade/components';
import Shimmer from 'common/components/Shimmer';
import React from 'react';

export const SettlementDetailsOverviewRevampShimmer = (): JSX.Element => {
  return (
    <Box
      backgroundColor="surface.background.level2.lowContrast"
      display="flex"
      flexDirection={{ base: 'column', m: 'row' }}
      padding="spacing.5"
      gap="spacing.5"
    >
      <Shimmer height="60px" width="60px" />
      <Box width="80%" display="flex" flexDirection="column" gap="spacing.3">
        <Shimmer height="25px" width="100%" />
        <Shimmer height="25px" width="100%" />
      </Box>
    </Box>
  );
};

export default SettlementDetailsOverviewRevampShimmer;
