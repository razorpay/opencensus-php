import React from 'react';
import { Amount, Box, Text } from '@razorpay/blade/components';

import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import {
  IAnalyticsProperties,
  ITodaysSettlement,
  TimelineItemIconKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import TodaySettlementMultiple from './TodaySettlementMultiple';
import TodaySettlementSingle from './TodaySettlementSingle';
import { TimelineItem } from './components/Timeline';

const TodaySettlement: React.FC<ITodaysSettlement & IAnalyticsProperties> = ({
  data,
  settlement_currency,
  isTodaySettlementPastProcessedSLA,
  isTodayMultipleSettlements,
  analyticsProperties,
}) => {
  const { total_amount, total_count } = data;

  return (
    <TimelineItem
      icon={
        isTodaySettlementPastProcessedSLA
          ? TimelineItemIconKeys.done
          : TimelineItemIconKeys.in_progress
      }
    >
      <Box display="flex" flexDirection="column" gap="spacing.2">
        <Text size="medium" weight="bold">
          {isTodayMultipleSettlements
            ? `Today, ${total_count} settlements worth`
            : "Today's settlement"}
        </Text>
        <Amount
          value={i18CurrencyConversionFromMinorUnitToCommonUnit(total_amount, settlement_currency)}
          currency={settlement_currency}
          size="heading-large-bold"
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
  );
};

export default TodaySettlement;
