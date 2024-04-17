import React, { useMemo } from 'react';
import { convertToMajorUnit } from '@razorpay/i18nify-js';
import { Amount, Box, Text } from '@razorpay/blade/components';

import {
  IAnalyticsProperties,
  ITodaysSettlement,
  TimelineItemIconKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import TodaySettlementMultiple from './TodaySettlementMultiple';
import TodaySettlementSingle from './TodaySettlementSingle';
import { TimelineItem } from './components/Timeline';
import { getTodaySingleSettlementContent } from './utils';
import { StatusImage } from './components/StatusImage';

const TodaySettlement: React.FC<ITodaysSettlement & IAnalyticsProperties> = ({
  data,
  settlement_currency,
  isTodaySettlementPastProcessedSLA,
  isTodayMultipleSettlements,
  analyticsProperties,
}) => {
  const { total_amount, total_count, title_key } = data;
  const { status } = useMemo(() => getTodaySingleSettlementContent(title_key), [title_key]);
  return (
    <Box display="flex" justifyContent="space-between">
      <TimelineItem
        icon={
          isTodaySettlementPastProcessedSLA
            ? TimelineItemIconKeys.done
            : TimelineItemIconKeys.in_progress
        }
      >
        <Box display="flex" flexDirection="column" gap="spacing.2">
          <Text size="medium" weight="semibold">
            {isTodayMultipleSettlements
              ? `Today, ${total_count} settlements worth`
              : "Today's settlement"}
          </Text>
          <Amount
            value={convertToMajorUnit(total_amount, { currency: settlement_currency })}
            currency={settlement_currency}
            type="heading"
            size="large"
            weight="semibold"
          />
          {isTodayMultipleSettlements ? (
            <TodaySettlementMultiple
              data={data}
              currency={settlement_currency}
              analyticsProperties={analyticsProperties}
            />
          ) : (
            <TodaySettlementSingle data={data} analyticsProperties={analyticsProperties} />
          )}
        </Box>
      </TimelineItem>
      {isTodayMultipleSettlements ? null : <StatusImage status={status} />}
    </Box>
  );
};

export default TodaySettlement;
