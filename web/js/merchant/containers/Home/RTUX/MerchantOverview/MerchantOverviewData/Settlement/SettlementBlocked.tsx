import React, { useMemo } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import {
  IAnalyticsProperties,
  UpcomingSettlementKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import GradientBox from './components/GradientBox';
import { upcommingSettlementBlockedContent } from './utils';
import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { Image } from 'merchant/containers/Home/RTUX/MerchantOverview/styled';

const SettlementBlocked: React.FC<
  {
    title_key: UpcomingSettlementKeys;
  } & IAnalyticsProperties
> = ({ title_key, analyticsProperties }) => {
  const { status, subheading, action } = useMemo(
    () => upcommingSettlementBlockedContent(title_key, analyticsProperties),
    [title_key],
  );

  const isMobile = useMobile(mobileBreakoints);
  return (
    <GradientBox>
      <Box display="flex" justifyContent="space-between" margin="spacing.6">
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
          <Text color="surface.text.gray.muted" size="small">
            {subheading}
          </Text>
          <Text size="small">{action}</Text>
        </Box>
        {!isMobile ? <Image src="/img/rtux/payment-unsuccessful.png" /> : null}
      </Box>
    </GradientBox>
  );
};

export default SettlementBlocked;
