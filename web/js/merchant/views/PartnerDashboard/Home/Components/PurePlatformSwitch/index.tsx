import React, { useState } from 'react';
import { Button, BottomSheet, BottomSheetBody, Link } from '@razorpay/blade/components';
import {
  PPSwitchWrapper,
  PPSwitchContent,
  PPCTAWrap,
  PPSwitchContentTitle,
  PPSwitchContentDesc,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { OpenModalT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import PurePlatformSwitchApplication from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow';
import { isMobileAndTablet, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';
import { trackingExperimentsProps } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';

interface PurePlatformSwitchGuideProps {
  openModal: OpenModalT;
  closeModal: () => void;
  trackingExperiments: trackingExperimentsProps;
}

const PurePlatformSwitchGuide = ({
  openModal,
  closeModal,
  trackingExperiments,
}: PurePlatformSwitchGuideProps): JSX.Element => {
  const [isOpen, setIsOpen] = useState(false);

  const trackReadMoreClick = () => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Read More Here',
      actionName: 'Clicked',
      screen: 'partner home page',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getExperimentsForTracking(window.rzp_user),
      },
    });
  };

  const openApplication = () => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Explore Now',
      actionName: 'Clicked',
      screen: 'partner home page',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getExperimentsForTracking(window.rzp_user),
      },
    });

    if (isMobileAndTablet()) {
      setIsOpen(true);
    } else {
      openModal({
        size: 'medium',
        component: (
          <PurePlatformSwitchApplication
            setIsOpen={setIsOpen}
            closeModal={closeModal}
            trackingExperiments={trackingExperiments}
          />
        ),
        className: 'bannerCarouselModal',
      });
    }
  };

  return (
    <PPSwitchWrapper>
      <PPSwitchContent>
        <PPSwitchContentTitle>
          Want to integrate &nbsp;
          <span className="pp-title-highlight">Razorpay Payments</span>
          &nbsp; on your platform
        </PPSwitchContentTitle>
        <PPSwitchContentDesc>
          Seamlessly manage payments for your clients with Razorpay APIs integrated on your
          platform.&nbsp;
          <Link
            href="https://razorpay.com/docs/partners/platform/payments-oauth"
            htmlTitle="Read more here"
            target="_blank"
            rel="noreferrer noopener"
            onClick={trackReadMoreClick}
          >
            Read more here
          </Link>
        </PPSwitchContentDesc>
      </PPSwitchContent>
      <PPCTAWrap>
        <Button onClick={openApplication} size="medium" type="button" variant="primary">
          Explore Now
        </Button>
      </PPCTAWrap>

      <BottomSheet
        isOpen={isOpen}
        snapPoints={[0.75, 0.8, 1.0]}
        onDismiss={() => {
          setIsOpen(false);
        }}
      >
        <BottomSheetBody>
          <PurePlatformSwitchApplication
            closeModal={closeModal}
            setIsOpen={setIsOpen}
            trackingExperiments={trackingExperiments}
          />
        </BottomSheetBody>
      </BottomSheet>
    </PPSwitchWrapper>
  );
};

export default PurePlatformSwitchGuide;
