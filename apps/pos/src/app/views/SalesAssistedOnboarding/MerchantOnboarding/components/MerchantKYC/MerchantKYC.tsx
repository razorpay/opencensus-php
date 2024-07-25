import { Box, Spinner, useToast } from '@razorpay/blade/components';
import React, { useEffect } from 'react';
import KYCRedirectionLoader from '../MerchantRegistration/KYCRedirectionLoader';
import redirectToEasyOnboarding from 'apps/pos/src/app/utils/redirectToEasyOnboarding';
import useMerchantSwitch from 'apps/pos/src/app/utils/hooks/useMerchantSwitch';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';

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
      });
    },
  });

  useEffect(() => {
    handleSwitchMerchant(merchantId);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
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
  );
};

export default MerchantKYC;
