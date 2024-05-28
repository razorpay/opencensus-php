import React, { useMemo } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import {
  IAnalyticsProperties,
  UpcomingSettlementKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import { upcommingSettlementBlockedContent } from './utils';
import { StatusImage } from './components/StatusImage';

const SettlementBlocked: React.FC<
  {
    title_key: UpcomingSettlementKeys;
  } & IAnalyticsProperties
> = ({ title_key, analyticsProperties }) => {
  const { status, subheading, action } = useMemo(
    () => upcommingSettlementBlockedContent(title_key, analyticsProperties),
    [title_key],
  );

  return (
    <Box
      display="flex"
      justifyContent="space-between"
      padding="spacing.6"
      backgroundImage="linear-gradient(90deg, #FDDDDD -2.86%, rgba(254, 228, 226, 0.50) 46.32%, rgba(255, 245, 245, 0.00) 102.53%)"
      borderRadius="large"
    >
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="center"
        gap="spacing.3"
        maxWidth="500px"
      >
        <Box>
          <Text display="inline" weight="semibold">
            Upcoming settlements are
          </Text>{' '}
          <Text display="inline" weight="semibold" color="feedback.text.negative.intense">
            {status}
          </Text>
        </Box>
        <Text color="surface.text.gray.subtle" size="small">
          {subheading}
        </Text>
        <Text size="small">{action}</Text>
      </Box>
      <StatusImage status={status ?? ''} />
    </Box>
  );
};

export default SettlementBlocked;
