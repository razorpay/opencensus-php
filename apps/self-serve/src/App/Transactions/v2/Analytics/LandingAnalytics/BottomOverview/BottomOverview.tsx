import React from 'react';
import { Box } from '@razorpay/blade/components';

import BottomOverviewCard from './BottomCard';
import { BottomOverviewProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { getBottomSectionData } from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { ScrollableContainer } from 'apps/self-serve/src/App/Transactions/v2/common/styled';

const BottomAnalyticsOverview = ({
  mode,
  currency,
  data,
  durationOption,
}: BottomOverviewProps): JSX.Element => {
  const { disputes, refund } = data;
  const bottomCardsData = getBottomSectionData({ data, mode });
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
