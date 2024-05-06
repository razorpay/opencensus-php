import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';

import RzpRewindReportBannerDesktop from 'assets/razorpay-rewind/desktop.png';
import RzpRewindReportBannerMobile from 'assets/razorpay-rewind/mobile.png';

import { BannerBtn } from './styled';

const trackPaymentsRecapEvent = ({
  objectName,
  actionName,
  properties = {},
}: {
  objectName: string;
  actionName: string;
  properties?: Record<string, any>;
}): void => {
  analyticsTrackWithUserInfo({
    objectName,
    actionName,
    screen: 'home page',
    properties,
    addUserProperties: true,
  });
};

const PaymentsRecapBanner: React.FC<{
  user: User;
  bannerVariant: 'desktop' | 'mobile';
}> = ({ user, bannerVariant }) => {
  const isMobileBanner = bannerVariant === 'mobile';
  const splitz = useSplitzService();
  const shouldShowBanner =
    isExperimentEnabled(splitz?.abExperiments?.payments_recap) && user?.isOrgRZP;

  useEffect(() => {
    trackPaymentsRecapEvent({
      objectName: 'Rzp rewind flipbook banner',
      actionName: 'displayed',
    });
  }, []);

  return shouldShowBanner ? (
    <Box
      height="100px"
      borderRadius="medium"
      position="relative"
      marginX="spacing.6"
      paddingTop="20px"
      elevation="highRaised"
    >
      <Box overflow="hidden" height="100%" width="100%" left="0px" position="relative">
        <img
          src={isMobileBanner ? RzpRewindReportBannerMobile : RzpRewindReportBannerDesktop}
          width="100%"
          height="100%"
          style={{ objectFit: 'cover' }}
        />
        <BannerBtn
          onClick={() => {
            trackPaymentsRecapEvent({
              objectName: 'Rzp rewind flipbook banner',
              actionName: 'clicked',
            });
            window.open('https://online.fliphtml5.com/tatrf/nvye/#p=1', '_blank');
          }}
          isMobileBanner={isMobileBanner}
        >
          View full report
        </BannerBtn>
      </Box>
    </Box>
  ) : null;
};

export default PaymentsRecapBanner;
