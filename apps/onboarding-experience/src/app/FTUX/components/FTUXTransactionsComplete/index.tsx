import React, { useState } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { useStore } from '@federated/apps/shell/commonStore';
import { ArrowRightIcon, Button } from '@razorpay/blade/components';
import { dashboardFetch, isMobileDevice } from '@libs/shared-utils';
import Banner, { BannerPropsType } from '@OnboardingExperienceCommons/components/Banner';
import TransactionBannerBg from '@OnboardingExperienceAssets/TransactionBannerBg.png';
import TransactionBannerBgMobile from '@OnboardingExperienceAssets/TransactionBannerBgMobile.png';
import TransactionBannerIcon from '@OnboardingExperienceAssets/TransactionBannerIcon.svg';
import { Wrapper } from 'apps/onboarding-experience/src/container';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import { analyticsTrackWithUserInfo } from '@libs/shared-utils';

const FTUXTransactionsComplete = ({
  moveToSettlementsView,
}: {
  moveToSettlementsView: () => void;
}) => {
  const showNotification = useStore((state) => state.showNotification);
  const mode = useStore((state) => state.session.mode);
  const isMobile = isMobileDevice();
  const [isLoading, setIsLoading] = useState(false);

  const handleMoveToSettlements = async () => {
    analyticsTrackWithUserInfo({
      objectName: 'FTUX Move To Settlements',
      actionName: 'Clicked',
      screen: 'home page',
    });
    setIsLoading(true);
    try {
      const response: { success: boolean; data: {} } = await dashboardFetch({
        url: `merchant/onboarding_custom_flags`,
        method: 'POST',
        data: {
          show_transaction_timeline: false,
        },
        mode,
      });
      if (response && response.success) {
        moveToSettlementsView();
      } else {
        throw new Error('An error occurred while proceeding to settlement balance!');
      }
    } catch (err) {
      errorService.captureError(err, {
        tags: {
          team: DASHBOARD_TEAMS.ONBOARDING_EXPERIENCE,
          module: 'FTUX_PROCEED_TO_SETTLEMENTS',
        },
        rank: errorService.ErrorRank.P0,
        extra: { info: err },
      });
      showNotification({
        type: 'error',
        message: 'Unable to proceed to settlements! - Please try again later',
      });
      setIsLoading(false);
    }
  };

  const bannerData: BannerPropsType = {
    title: 'You have accepted 5 transactions!',
    description:
      'Great job! Now that you have gotten the hang of it, we would want to show you your settlements view.',
    CTA: () => (
      <Button
        onClick={handleMoveToSettlements}
        size={isMobile ? 'small' : 'medium'}
        icon={ArrowRightIcon}
        iconPosition="right"
        data-analytics-name="proceed-to-settlements-cta"
        isLoading={isLoading}
      >
        Show me my settlements balance
      </Button>
    ),
    iconSrc: TransactionBannerIcon,
    bgImage: TransactionBannerBg,
    bgImageMobile: TransactionBannerBgMobile,
  };

  return (
    <Wrapper>
      <Banner {...bannerData} />
    </Wrapper>
  );
};

export default FTUXTransactionsComplete;
