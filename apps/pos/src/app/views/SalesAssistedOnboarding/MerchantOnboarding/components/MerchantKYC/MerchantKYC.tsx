import { Box, Spinner, useToast } from '@razorpay/blade/components';
import React, { useEffect } from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import KYCRedirectionLoader from '../MerchantRegistration/KYCRedirectionLoader';
import redirectToEasyOnboarding from 'apps/pos/src/app/utils/redirectToEasyOnboarding';
import useMerchantSwitch from 'apps/pos/src/app/utils/hooks/useMerchantSwitch';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const MerchantKYC = (): JSX.Element => {
  const { values } = useOnboardingContext();
  const { merchantId } = values;
  const toast = useToast();

  const { handleSwitchMerchant } = useMerchantSwitch({
    onSuccess: () => {
      redirectToEasyOnboarding(true);
    },
    onError: () => {
      toast.show({
        color: 'negative',
        content: 'Failed to switch merchant',
        autoDismiss: true,
      });
    },
  });

  useEffect(() => {
    handleSwitchMerchant(merchantId);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <ErrorBoundary
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.MERCHANT_KYC }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <Box
        margin="spacing.5"
        height="300px"
        display="flex"
        justifyContent="center"
        alignItems="center"
      >
        <Spinner size="large" accessibilityLabel="Loading KYC details" />
        <KYCRedirectionLoader isOpen />
      </Box>
    </ErrorBoundary>
  );
};

export default MerchantKYC;
