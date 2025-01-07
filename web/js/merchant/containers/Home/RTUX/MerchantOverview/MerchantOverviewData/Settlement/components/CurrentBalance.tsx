import {
  Amount,
  Box,
  Heading,
  InfoIcon,
  Tooltip,
  TooltipInteractiveWrapper,
  useTheme,
} from '@razorpay/blade/components';
import React from 'react';
import { CURRENT_BALANCE_TOOLTIP } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';
import { useBreakpoint } from '@razorpay/blade/utils';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

const CurrentBalance = ({ data }) => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const settlementData = data?.hero_card_data?.settlement;
  const isLargeScreen = matchedBreakpoint === 'xl';
  const current = settlementData?.current_balance || 0;
  const current_currency = settlementData?.current_balance_currency || 'INR';
  const current_amount = i18nifyConvertToMajorUnit(current, current_currency);
  return (
    <Box display="flex" gap="spacing.4" alignItems="center">
      <Box display="flex" gap="spacing.2" alignItems="center" height="fit-content">
        <Heading weight="semibold" size="medium">
          Current balance
        </Heading>
        <Box alignSelf="inherit" marginTop={isLargeScreen ? 'spacing.3' : 'spacing.2'}>
          <Tooltip content={CURRENT_BALANCE_TOOLTIP}>
            <TooltipInteractiveWrapper>
              <InfoIcon size="medium" color="interactive.icon.gray.muted" />
            </TooltipInteractiveWrapper>
          </Tooltip>
        </Box>
      </Box>
      <Amount
        currency={current_currency}
        value={current_amount}
        size="medium"
        type="heading"
        weight="semibold"
      />
    </Box>
  );
};

export default CurrentBalance;
