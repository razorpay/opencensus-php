import React, { useMemo } from 'react';

import {
  IAnalyticsProperties,
  IUpcommingSettlement,
  TimelineItemIconKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import { TimelineItem } from './components/Timeline';
import TimelineCardWithAmount from './components/TimelineCardWithAmount';
import { getUpcommingSettlementContent } from './utils';

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
  );
};

export default UpcommingSettlement;
