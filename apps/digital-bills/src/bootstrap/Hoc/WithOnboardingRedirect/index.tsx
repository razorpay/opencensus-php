import React, { useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import { getMode } from '@apps/digital-bills/src/utils/sharedUtils';
import { GET_ONBOARDING_STATUS_QUERY } from '@apps/digital-bills/src/bootstrap/Hoc/WithOnboardingRedirect/queries';
import type { OnboardingStatusResponse } from '@apps/digital-bills/src/bootstrap/Hoc/WithOnboardingRedirect/types';
import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';
import useAppStore from '@apps/digital-bills/src/bootstrap/Store';
import Onboarding from '@apps/digital-bills/src/views/Onboarding';
import {
  DIGITAL_BILLING_PRODUCT_TYPE,
  ONBOARDING_STATUS,
} from '@apps/digital-bills/src/views/Onboarding/constants';

const WithOnboardingRedirect = (WrappedComponent: React.FC) => {
  const OnboardingRedirectWrapper = () => {
    const { onboardingStatus, updateOnboardingStatus } = useAppStore();
    const {
      data: onboardingStatusReponse,
      isFetching,
      isError,
      refetch,
    } = useQuery<OnboardingStatusResponse>({
      enabled: getMode(window.rzp_user?.id) === 'test',
      queryKey: ['digitalBillingOnboardingStatus'],
      refetchOnWindowFocus: false,
      retry: false,
      queryFn: () =>
        graphqlRequest({
          document: GET_ONBOARDING_STATUS_QUERY,
          variables: { productType: DIGITAL_BILLING_PRODUCT_TYPE },
        }),
    });

    useEffect(() => {
      if (onboardingStatusReponse) {
        updateOnboardingStatus(onboardingStatusReponse.merchantOnboardingStatus);
      }
    }, [onboardingStatusReponse]);

    // Onboarding status check is only done for 'test' mode
    // In 'live' mode, BillMe routes will be under Splitz experiment and will be enabled for BillMe specific Merchants
    if (getMode(window.rzp_user?.id) === 'live') return <WrappedComponent />;

    if (isError)
      return (
        <Box marginTop="spacing.9">
          <RetryOnError
            errorText="Error in fetching Merchant onboarding status"
            retryFn={refetch}
          />
        </Box>
      );
    if (isFetching) {
      return (
        <Box height="80vh" display="flex" alignItems="center" justifyContent="center">
          <Spinner size="xlarge" accessibilityLabel="Fetching Merchant Onboarding Status" />
        </Box>
      );
    }
    if (onboardingStatus !== ONBOARDING_STATUS.ACTIVATED) {
      return (
        <Onboarding
          isInWaitlist={
            onboardingStatus === ONBOARDING_STATUS.PENDING ||
            onboardingStatus === ONBOARDING_STATUS.INITIATED
          }
        />
      );
    }
    return <WrappedComponent />;
  };
  return OnboardingRedirectWrapper;
};

export default WithOnboardingRedirect;
