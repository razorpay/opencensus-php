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
      return (
        <Text type="subtle" contrast="low">
          {refundCount} processed
        </Text>
      );
    case PaymentTypes.Disputes:
      return (
        <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.3">
          <Text type="subtle" contrast="low">
            {openDisputesCount} open
          </Text>
          <img src={GreyDotIcon} alt="grey dot" />
          <Text type="subtle" contrast="low">
            {underReviewDisputesCount} under-review
          </Text>
        </Box>
      );
    case PaymentTypes.Failed:
      return (
        <Text type="subtle" contrast="low">
          payments
        </Text>
      );
    /* istanbul ignore next */
    default:
      return null;
  }
};

export default CardFooter;
