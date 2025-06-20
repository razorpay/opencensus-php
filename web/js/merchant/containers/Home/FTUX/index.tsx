import React, { Suspense, useEffect } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { Box } from '@razorpay/blade/components';
import lazyLoader from 'merchant/routes/LazyLoader';
import LayoutLoader from './Loader';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { closeModal as close2faModal } from 'merchant_common/reducers/modals';
import { dashboardFetch } from '@libs/shared-utils';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import { useStore } from '@apps/shell/src/client/store/commonStore';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

const FTUXLanding = lazyLoader(
  () =>
    import(
      /* webpackChunkName: "FTUXLanding" */ '@federated/apps/onboarding-experience/entry/FTUX'
    ),
);

const FTUXHomepage = (): JSX.Element => {
  const { user, mode } = useStore((state) => state.session);
  const { criticalFlow } = useTwoFactorVerificationContext();
  const { abExperiments } = useSplitzService();

  const isTransactionTimelineEnabled = isExperimentEnabled(
    abExperiments?.['ftuxV2_transaction_timeline'],
  );

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

  const handleFirstEntryInFTUX = async () => {
    /* In case of RTUX experiment:
        If user has landed on FTUX for the first time, i.e,
        not yet transacted and the flags are not yet set, set the flags
        to handle post transaction journey
    */
    if (
      isTransactionTimelineEnabled &&
      user &&
      user.isTransacted === false &&
      !user.show_ftux_dashboard
    ) {
      try {
        const response: { success: boolean; data: {} } = await dashboardFetch({
          url: `merchant/onboarding_custom_flags`,
          method: 'POST',
          data: {
            show_ftux_dashboard: true,
            show_transaction_timeline: true,
          },
          mode,
        });
        if (!response || !response.success) {
          throw new Error('An error occurred while setting merchant onboarding flags!');
        }
      } catch (err) {
        errorService.captureError(err, {
          tags: {
            team: DASHBOARD_TEAMS.ONBOARDING_EXPERIENCE,
            module: 'FTUX_ONBOARDING_FLAGS',
          },
          rank: errorService.ErrorRank.P0,
          extra: { info: err },
        });
      }
    }
  };

  useEffect(() => {
    handleFirstEntryInFTUX();
  }, []);

  return (
    <Box>
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
