import React, { useMemo } from 'react';
import { Box, Text } from '@razorpay/blade/components';

import {
  IAnalyticsProperties,
  ITodaySettlementData,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import Dot from './components/Dot';
import Status from './components/Status';
import { getTodaySingleSettlementContent } from './utils';

const TodaySettlementSingle: React.FC<
  {
    data: ITodaySettlementData;
  } & IAnalyticsProperties
> = ({ data, analyticsProperties }) => {
  const { title_key } = data;
  const { status, subheading, action } = useMemo(
    () => getTodaySingleSettlementContent(title_key, analyticsProperties),
    [title_key],
  );
  return (
    <Box
      display="flex"
      alignItems={{ base: 'start', l: 'center' }}
      gap="spacing.2"
      flexDirection={{ base: 'column', l: 'row' }}
    >
      <Status status={status} />
      {subheading ? (
        <>
          <Dot />
          <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
            {subheading}
          </Text>
        </>
      ) : null}
      {!!action ? (
        <>
          <Dot />
          {action}
        </>
      ) : null}
    </Box>
  );
};

export default TodaySettlementSingle;
