import React from 'react';
import { Box } from '@razorpay/blade/components';

import { useI18Service } from 'common/i18';
import { BottomOverviewProps, PaymentTypes } from 'merchant/views/Transactions/v2/Analytics/types';
import { getBottomSectionData } from 'merchant/views/Transactions/v2/Analytics/utils';
import { ScrollableContainer } from 'merchant/views/Transactions/v2/common/styled';

import BottomOverviewCard from './BottomCard';

const BottomAnalyticsOverview = ({
  mode,
  currency,
  data,
  durationOption,
}: BottomOverviewProps): JSX.Element => {
  const { disputes, refund } = data;
  const { isConfigTagEnabled } = useI18Service();

  let bottomCardsData = getBottomSectionData({ data, mode });

  // Filtering the bottomCardsData for i18n, to show/hide the products based on the country config key.
  bottomCardsData = bottomCardsData.filter((data) => {
    if (isConfigTagEnabled('refunds.refund') && data.name === PaymentTypes.Refunds) {
      return false;
    } else if (isConfigTagEnabled('disputes.disputes') && data.name === PaymentTypes.Disputes) {
      return false;
    }

    return true;
  });

  return (
    <ScrollableContainer>
      <Box
        display="flex"
        flexDirection="row"
        justifyContent="space-between"
        gap="spacing.5"
        paddingX="spacing.1"
      >
        {bottomCardsData.map((data, index) => (
          <BottomOverviewCard
            key={index}
            data={data}
            currency={currency}
            footerValues={{
              refundCount: refund.count,
              openDisputesCount: disputes.open,
              underReviewDisputesCount: disputes.underReview,
            }}
            durationOption={durationOption}
          />
        ))}
      </Box>
    </ScrollableContainer>
  );
};

export default BottomAnalyticsOverview;
