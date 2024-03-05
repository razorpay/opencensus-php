import React from 'react';
import { Box } from '@razorpay/blade/components';

import CapturedPaymentCard from './CapturedPaymentCard';
import PaymentMethodSplit from './PaymentMethodSplit';
import LoadFailed from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/LoadFailed';
import { CapturePaymentShimmer } from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/Shimmer';
import { TopOverviewContainerProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { ScrollableContainer } from 'apps/self-serve/src/App/Transactions/v2/common/styled';

const TopOverviewContainer = ({
  isPaymentsDataLoading,
  isPaymentsDataFailed,
  paymentCapturedAmount,
  paymentCapturedCount,
  paymentByMethod,
  isMobile,
  currency,
  shouldShowSrBanner,
  successRateData,
  durationOption,
}: TopOverviewContainerProps): JSX.Element => {
  if (isPaymentsDataFailed) {
    return (
      <LoadFailed
        title="We couldn't load the summary of your transactions."
        subtitle="Refresh to try again"
        height={200}
      />
    );
  }
  return (
    <ScrollableContainer>
      <Box display="flex" flexDirection="row" justifyContent="space-between" gap="spacing.5">
        {isPaymentsDataLoading ? (
          <CapturePaymentShimmer />
        ) : (
          <>
            <Box flex={1}>
              <CapturedPaymentCard
                paymentCapturedAmount={paymentCapturedAmount}
                paymentCapturedCount={paymentCapturedCount}
                isMobile={isMobile}
                currency={currency}
                durationOption={durationOption}
              />
            </Box>
            {paymentByMethod.length > 0 ? (
              <Box flex={1}>
                <PaymentMethodSplit
                  shouldShowSrBanner={shouldShowSrBanner}
                  successRateData={successRateData}
                  isMobile={isMobile}
                  paymentByMethod={paymentByMethod}
                  durationOption={durationOption}
                />
              </Box>
            ) : null}
          </>
        )}
      </Box>
    </ScrollableContainer>
  );
};

export default TopOverviewContainer;
