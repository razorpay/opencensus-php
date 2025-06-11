import React, { Suspense } from 'react';
import { Alert, Box, DotIcon } from '@razorpay/blade/components';
import lazyLoader from 'merchant/routes/LazyLoader';
import LayoutLoader from './Loader';
import { isMobileDevice } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
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
  const { mode } = useStore((state) => state.session);
  const isMobile = isMobileDevice();

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
    <Box>
      {mode === 'test' && (
        <Alert
          description="You are in Test mode - you can test payments and features here. Switch to Live Mode (bottom left) to accept real transactions."
          icon={DotIcon}
          isFullWidth
          color="notice"
          isDismissible={false}
        />
      )}
      <Box display="flex" justifyContent="center">
        <Box maxWidth="920px" width="100%" paddingTop="spacing.2">
          <Suspense fallback={<LayoutLoader />}>
            <FTUXLanding triggerTwoFaAuth={triggerTwoFaAuth} />
          </Suspense>
        </Box>
      </Box>
    </Box>
  );
};

export default FTUXHomepage;
