import React from 'react';
import { Text, Box } from '@razorpay/blade/components';

import GreyDotIcon from 'assets/transactions/grey-dot.svg';
import {
  BottomOverviewCardFooterProps,
  PaymentTypes,
} from 'merchant/views/Transactions/v2/Analytics/types';

const CardFooter = ({
  name,
  values,
}: {
  name: PaymentTypes;
  values: BottomOverviewCardFooterProps;
}): JSX.Element | null => {
  const { refundCount, openDisputesCount, underReviewDisputesCount } = values;
  switch (name) {
    case PaymentTypes.Refunds:
      return <Text color="surface.text.gray.subtle">{refundCount}processed</Text>;
    case PaymentTypes.Disputes:
      return (
        <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.3">
          <Text color="surface.text.gray.subtle">{openDisputesCount} open</Text>
          <img src={GreyDotIcon} alt="grey dot" />
          <Text color="surface.text.gray.subtle">{underReviewDisputesCount} under-review</Text>
        </Box>
      );
    case PaymentTypes.Failed:
      return <Text color="surface.text.gray.subtle">payments</Text>;
    /* istanbul ignore next */
    default:
      return null;
  }
};

export default CardFooter;
