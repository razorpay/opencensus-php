import React from 'react';
import { Amount, Box, Text } from '@razorpay/blade/components';

import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { ITimelineItemWithAmount } from 'merchant/containers/Home/RTUX/MerchantOverview/types';

import Dot from './Dot';
import Status from './Status';

const TimelineCardWithAmount: React.FC<ITimelineItemWithAmount> = ({
  amount,
  currency,
  status,
  heading,
  subheading,
  action,
}) => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.2">
      <Text size="medium" weight="semibold">
        {heading}
      </Text>
      <Amount
        value={i18CurrencyConversionFromMinorUnitToCommonUnit(amount, currency)}
        currency={currency}
        type="heading"
        size="large"
        weight="semibold"
      />
      <Box
        display="flex"
        gap="spacing.2"
        flexDirection={{ base: 'column', l: 'row' }}
        alignItems={{ base: 'start', l: 'center' }}
      >
        <Status status={status} />
        <Dot />
        <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
          {subheading}
        </Text>
        {action ? (
          <>
            <Dot />
            {action}
          </>
        ) : null}
      </Box>
    </Box>
  );
};

export default TimelineCardWithAmount;
