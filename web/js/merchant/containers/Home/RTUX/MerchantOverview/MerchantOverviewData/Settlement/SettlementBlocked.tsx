import React, { useMemo } from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
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
            <Text display="inline" weight="bold">
              Upcoming settlements are
            </Text>{' '}
            <Text display="inline" weight="bold" color="feedback.text.negative.lowContrast">
              {status}
            </Text>
          </Box>
          <Heading variant="subheading" color="surface.text.subdued.lowContrast">
            {subheading}
          </Heading>
          <Text size="small">{action}</Text>
        </Box>
        {!isMobile ? <Image src="/img/rtux/payment-unsuccessful.png" /> : null}
      </Box>
    </GradientBox>
  );
};

export default SettlementBlocked;
