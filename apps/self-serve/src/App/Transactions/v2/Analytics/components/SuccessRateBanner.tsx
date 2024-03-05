import React from 'react';
import { Box, ChevronRightIcon, Link, Text } from '@razorpay/blade/components';
import { useLocation, useNavigate } from 'react-router-dom';

import SuccessRateEmoji from 'apps/self-serve/src/assets/success-rate-emoji.svg';
import { SuccessRateBannerProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

const SuccessRateBanner = ({
  successRateData,
  section,
}: SuccessRateBannerProps): JSX.Element | null => {
  const navigate = useNavigate();
  const location = useLocation();

  return (
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
          navigate(TransactionsEntityRoute.SUCCESS_RATE, {
            state: { prevPath: location.pathname },
          });
        }}
        variant="button"
      >
        View dashboard
      </Link>
    </Box>
  );
};

export default SuccessRateBanner;
