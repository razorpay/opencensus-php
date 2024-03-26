import React, { useMemo } from 'react';
import {
  Amount,
  Box,
  Heading,
  InfoIcon,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import {
  IAnalyticsProperties,
  ISettlement,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import PreviousSettlement from './PreviousSettlement';
import SettlementBlocked from './SettlementBlocked';
import TodaySettlement from './Today';
import UpcommingSettlement from './UpcommingSettlement';
import { Timeline } from './components/Timeline';
import { getSettlementConfig } from './utils';

const Settlement: React.FC<ISettlement & IAnalyticsProperties> = ({
  settlement,
  analyticsProperties,
}) => {
  const {
    current_balance,
    current_balance_currency,
    previous,
    settlement_currency,
    today,
    upcoming_settlement,
  } = settlement;

  const {
    shouldShowUpcommingBlock,
    shouldShowToday,
    shouldShowOnlyPreviousSettlement,
    shouldShowPrevious,
    shouldShowUpcomming,
    isTodayMultipleSettlements,
    isTodaySettlementPastProcessedSLA,
  } = useMemo(() => getSettlementConfig(settlement), [settlement]);

  return (
    <Box
      display="flex"
      flexDirection={{ base: 'column', l: 'row' }}
      gap="spacing.6"
      marginX={{ base: 'spacing.2', l: 'spacing.4' }}
    >
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.4"
        justifyContent="center"
        width={{ base: '100%', l: '30%' }}
      >
        <Box display="flex" gap="spacing.2" alignItems="flex-end">
          <Heading size="medium">Current balance</Heading>
          <Tooltip content="This is the total amount that is due to be deposited in your bank account after deduction of taxes, platform fees, any other applicable charges, and adjustment of refunds and credits">
            <TooltipInteractiveWrapper>
              <InfoIcon size="small" color="surface.text.muted.lowContrast" />
            </TooltipInteractiveWrapper>
          </Tooltip>
        </Box>
        <Amount
          value={i18CurrencyConversionFromMinorUnitToCommonUnit(
            current_balance,
            current_balance_currency,
          )}
          currency={current_balance_currency}
          size="title-medium"
        />
      </Box>
      <Box display="flex" flexDirection="column" justifyContent="space-between" width="100%">
        {shouldShowUpcommingBlock && upcoming_settlement?.title_key ? (
          <SettlementBlocked
            title_key={upcoming_settlement.title_key}
            analyticsProperties={analyticsProperties}
          />
        ) : (
          <Timeline>
            {shouldShowToday && today ? (
              <TodaySettlement
                data={today}
                settlement_currency={settlement_currency}
                isTodaySettlementPastProcessedSLA={isTodaySettlementPastProcessedSLA}
                isTodayMultipleSettlements={isTodayMultipleSettlements}
                analyticsProperties={analyticsProperties}
              />
            ) : null}
            {shouldShowUpcomming && upcoming_settlement ? (
              <UpcommingSettlement
                analyticsProperties={analyticsProperties}
                data={upcoming_settlement}
                settlement_currency={settlement_currency}
              />
            ) : null}
            {shouldShowPrevious && previous ? (
              <PreviousSettlement
                data={previous}
                settlement_currency={settlement_currency}
                showOnlyPreviousSettlement={shouldShowOnlyPreviousSettlement}
                analyticsProperties={analyticsProperties}
              />
            ) : null}
          </Timeline>
        )}
      </Box>
    </Box>
  );
};

export default Settlement;
