import React from 'react';
import { Box, Heading, Skeleton, Text } from '@razorpay/blade/components';

import { TimelineItemIconKeys } from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import { Timeline, TimelineItem } from './components/Timeline';

const SettlementLoader = ({ isRiskFohMerchant = false }) => (
  <Box
    display="flex"
    flexDirection={{ base: 'column', l: 'row' }}
    gap="spacing.6"
    marginX={{ base: 'spacing.2', l: 'spacing.4' }}
    testID="settlement-shimmer"
    padding={isRiskFohMerchant ? 'spacing.6' : 'spacing.0'}
  >
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.4"
      justifyContent="center"
      width={{ base: '100%', l: '30%' }}
    >
      <Heading size="medium">Current balance</Heading>
      <Skeleton width="250px" height="24px" borderRadius="max" />
    </Box>
    <Box display="flex" flexDirection="column" justifyContent="space-between" width="100%">
      <Timeline>
        <TimelineItem icon={TimelineItemIconKeys.loading}>
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text size="small">Today’s settlement</Text>
            <Skeleton width="110px" height="24px" borderRadius="max" />
            <Skeleton width="180px" height="14px" borderRadius="medium" />
          </Box>
        </TimelineItem>
        <TimelineItem icon={TimelineItemIconKeys.loading}>
          <Skeleton width="120px" height="16px" borderRadius="max" />
        </TimelineItem>
      </Timeline>
    </Box>
  </Box>
);

export default SettlementLoader;
