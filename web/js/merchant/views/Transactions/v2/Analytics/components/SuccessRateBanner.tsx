import React from 'react';
import { Box, ChevronRightIcon, Link, Text } from '@razorpay/blade/components';
import { withRouter } from 'react-router-dom';

import SuccessRateEmoji from 'assets/transactions/success-rate-emoji.svg';
import { SuccessRateBannerProps } from 'merchant/views/Transactions/v2/Analytics/types';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';

const SuccessRateBanner = ({
  successRateData,
  history,
  location,
  section,
}: SuccessRateBannerProps): JSX.Element | null => (
  <Box display="flex" gap="spacing.2" alignItems="center">
    {successRateData >= 75 ? (
      <Box display="flex" gap="spacing.3">
        <img src={SuccessRateEmoji} alt="success rate emoji" />
      </Box>
    ) : null}
    <Text weight="bold">{successRateData}% success rate</Text>
    <Link
      icon={ChevronRightIcon}
      iconPosition="right"
      onClick={() => {
        track({
          objectName: 'Success Rate More Details',
          properties: {
            section,
          },
        });
        history.push({
          pathname: TransactionsEntityRoute.SUCCESS_RATE,
          state: { prevPath: location.pathname },
        });
      }}
      variant="button"
    >
      Details
    </Link>
  </Box>
);

export default withRouter(SuccessRateBanner);
