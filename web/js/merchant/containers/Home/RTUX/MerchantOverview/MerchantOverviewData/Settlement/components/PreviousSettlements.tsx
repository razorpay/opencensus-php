import React from 'react';
import { Amount, Box, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';
import { SettlementStatusBadge } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { capitalizeFirstLetter } from 'common/utils/rzp-utils';
import { getFormattedDateFromTimestamp } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';

const PreviousSettlements = ({ data }) => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const settlementData = data?.hero_card_data?.settlement;
  const prev = settlementData?.previous?.total_amount;
  const current_currency = settlementData?.current_balance_currency || 'INR';
  const createdAtTimestamp = settlementData?.previous?.created_at;

  const prev_amount = i18nifyConvertToMajorUnit(prev || 0, current_currency);
  const isSmallScreen = matchedBreakpoint === 's' || matchedBreakpoint === 'xs';

  if (!prev || !createdAtTimestamp) {
    return null; // Return null if no previous settlement data is available or if the created_at timestamp is not available
  }
  const lastDepositDate = getFormattedDateFromTimestamp(createdAtTimestamp);

  return (
    <Box display="flex" gap="spacing.3" alignItems="center" height="fit-content">
      {isSmallScreen ? (
        <Box display="flex" gap="spacing.3" alignItems="baseline" height="fit-content">
          <Text variant="body" weight="medium" color="surface.text.gray.subtle">
            Last Deposit:
          </Text>
          <Amount value={prev_amount} currency={current_currency} size="medium" />
          <Text color="interactive.text.positive.subtle" size="medium" weight="semibold">
            {capitalizeFirstLetter(SettlementStatusBadge.processed)}
          </Text>
        </Box>
      ) : (
        <Box display="flex" gap="spacing.3" alignItems="baseline" height="fit-content">
          <Amount value={prev_amount} size="large" currency={current_currency} />
          <Text variant="body" weight="medium" color="surface.text.gray.subtle">
            Last deposited on {lastDepositDate}
          </Text>
          <Text color="interactive.text.positive.subtle" size="medium" weight="semibold">
            {capitalizeFirstLetter(SettlementStatusBadge.processed)}
          </Text>
        </Box>
      )}
    </Box>
  );
};

export default PreviousSettlements;
