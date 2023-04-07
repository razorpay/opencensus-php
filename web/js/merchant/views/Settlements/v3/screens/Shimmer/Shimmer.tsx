import { Box } from '@razorpay/blade/components';
import BreakupShimmer from 'merchant/views/Settlements/v3/components/Breakup/Shimmer';
import SettlementInfoShimmer from 'merchant/views/Settlements/v3/components/SettlementInfo/Shimmer';
import TimelineShimmer from 'merchant/views/Settlements/v3/components/Timeline/Shimmer';
import React from 'react';

const FullPageShimmer = (): JSX.Element => {
  return (
    <>
      <SettlementInfoShimmer />
      <Box
        display="flex"
        flexWrap="wrap"
        gap="spacing.5"
        flexDirection={{ base: 'column', m: 'row' }}
      >
        <BreakupShimmer />
        <TimelineShimmer />
      </Box>
    </>
  );
};

export default FullPageShimmer;
