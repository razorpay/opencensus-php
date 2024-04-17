import React, { useMemo } from 'react';
import {
  IAnalyticsProperties,
  IUpcommingSettlement,
  TimelineItemIconKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { Box } from '@razorpay/blade/components';

import { TimelineItem } from './components/Timeline';
import TimelineCardWithAmount from './components/TimelineCardWithAmount';
import { getUpcommingSettlementContent } from './utils';
import { StatusImage } from './components/StatusImage';

const UpcommingSettlement: React.FC<IUpcommingSettlement & IAnalyticsProperties> = ({
  data,
  settlement_currency,
  analyticsProperties,
}) => {
  const { settlement_amount, title_key, next_settlement_time } = data;

  const { status, subheading, action } = useMemo(
    () =>
      getUpcommingSettlementContent({
        title_key,
        next_settlement_time,
        settlement_currency,
        analyticsProperties,
      }),
    [title_key, next_settlement_time, settlement_currency],
  );

  return (
    <Box display="flex" justifyContent="space-between">
      <Box minHeight={{ base: 'auto', m: '100px' }}>
        <TimelineItem icon={TimelineItemIconKeys.in_progress}>
          <TimelineCardWithAmount
            amount={settlement_amount || 0}
            currency={settlement_currency}
            status={status}
            heading="Upcoming settlement"
            subheading={subheading}
            action={action}
          />
        </TimelineItem>
      </Box>
      <StatusImage status={status} />
    </Box>
  );
};

export default UpcommingSettlement;
