import React, { Suspense } from 'react';
import { Box } from '@razorpay/blade/components';
import lazyLoader from 'merchant/routes/LazyLoader';
import LayoutLoader from './Loader';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { closeModal as close2faModal } from 'merchant_common/reducers/modals';

const FTUXLanding = lazyLoader(
  () =>
    import(
      /* webpackChunkName: "FTUXLanding" */ '@federated/apps/onboarding-experience/entry/FTUX'
    ),
);

const FTUXHomepage = (): JSX.Element => {
  const { criticalFlow } = useTwoFactorVerificationContext();

  const triggerTwoFaAuth = ({
    enforceVerifyOtp,
    modes = ['live'],
    onUserTwoFaVerified,
    onWrongOtpCallback,
    onFlowTermination,
  }) =>
    criticalFlow({
      enforceVerifyOtp,
      modes,
      onUserTwoFaVerified: () => {
        onUserTwoFaVerified();
        close2faModal();
      },
      onWrongOtpCallback,
      onFlowTermination,
    });

  return (
    <Box display="flex" justifyContent="center">
      <Box maxWidth="920px" width="100%">
        <Suspense fallback={<LayoutLoader />}>
          <FTUXLanding triggerTwoFaAuth={triggerTwoFaAuth} />
        </Suspense>
      </Box>
    </Box>
  );
};

export default FTUXHomepage;
