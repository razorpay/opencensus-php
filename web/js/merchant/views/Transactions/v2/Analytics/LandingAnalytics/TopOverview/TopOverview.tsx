import React from 'react';
import { ScrollableContainer } from 'merchant/views/Transactions/v2/Analytics/styled';
import CapturedPaymentCard from './CapturedPaymentCard';
import PaymentMethodSplit from './PaymentMethodSplit';
import { Box } from '@razorpay/blade/components';
import { CapturePaymentShimmer } from 'merchant/views/Transactions/v2/Analytics/components/Shimmer';
import { TopOverviewContainerProps } from 'merchant/views/Transactions/v2/Analytics/types';
import LoadFailed from 'merchant/views/Transactions/v2/Analytics/components/LoadFailed';

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
