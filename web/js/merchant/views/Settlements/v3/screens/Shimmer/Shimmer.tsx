import { Box } from '@razorpay/blade/components';
import BreakupShimmer, {
  BreakupRevampShimmer,
} from 'merchant/views/Settlements/v3/components/Breakup/Shimmer';
import SettlementInfoShimmer, {
  SettlementInfoRevampShimmer,
} from 'merchant/views/Settlements/v3/components/SettlementInfo/Shimmer';
import TimelineShimmer, {
  TimelineRevampShimmer,
} from 'merchant/views/Settlements/v3/components/Timeline/Shimmer';
import React from 'react';
import SettlementDetailsOverviewRevampShimmer from 'merchant/views/Settlements/v3/components/SettlementDetailsOverview/Shimmer';

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

export const FullPageRevampShimmer = (): JSX.Element => {
  return (
    <Box display="flex" gap="spacing.5" flexDirection={{ base: 'column', xl: 'row', l: 'row' }}>
      <Box display="flex" flex="2" gap="spacing.5" flexDirection="column">
        <Box display="flex" gap={{ base: 'spacing.1', m: 'spacing.5' }} flexDirection="column">
          <SettlementDetailsOverviewRevampShimmer />
          <BreakupRevampShimmer />
        </Box>
        <SettlementInfoRevampShimmer />
      </Box>
      <TimelineRevampShimmer />
    </Box>
  );
};

export default FullPageShimmer;
