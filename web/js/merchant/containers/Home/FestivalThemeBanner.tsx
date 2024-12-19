import React, { useEffect } from 'react';
import { ArrowRightIcon, Box, Button, Text } from '@razorpay/blade/components';
import desktopBackground from 'assets/christmas-banner/desktop-background.svg';
import mobileBackground from 'assets/christmas-banner/mobile-background.svg';

import { useMobile } from 'common/hooks/useMobile';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

const track = (actionName) =>
  analyticsTrackWithUserInfo({
    objectName: 'Festival theme banner',
    actionName,
    screen: 'homepage',
    addUserProperties: true,
  });

const FestivalThemeBanner = ({ isRtux = false, isMobileView = false }) => {
  const isMobile = useMobile();
  const splitz = useSplitzService();
  const shouldShowBanner = isExperimentEnabled(splitz?.abExperiments?.checkout_festival_theme);

  useEffect(() => {
    if (shouldShowBanner) {
      track('displayed');
    }
  }, [shouldShowBanner]);

  if (!shouldShowBanner) {
    return null;
  }

  return (
    <Box
      display="flex"
      flexDirection={isMobile ? 'column' : 'row'}
      alignItems="center"
      justifyContent="center"
      gap={isMobile ? 'spacing.3' : 'spacing.7'}
      padding="spacing.4"
      backgroundImage={`url("${isMobile ? mobileBackground : desktopBackground}")`}
      backgroundRepeat="no-repeat"
      backgroundSize="cover"
      backgroundPosition="center center"
      borderRadius="medium"
      marginX={
        isRtux ? { base: 'spacing.0', m: 'spacing.6' } : isMobileView ? 'spacing.5' : 'spacing.6'
      }
    >
      <Box display="flex" flexDirection="row" alignItems="center">
        <Text color="interactive.text.staticWhite.normal" size="large" display="inline">
          Christmas theme is now live!
        </Text>
      </Box>
      <Box padding="spacing.1">
        <Button
          variant="primary"
          color="white"
          icon={ArrowRightIcon}
          iconPosition="right"
          size="medium"
          onClick={() => {
            track('clicked');
            window.open(
              `${window.location.origin}/app/checkout-settings/checkout-styling`,
              '_blank',
            );
          }}
        >
          View in checkout editor
        </Button>
      </Box>
    </Box>
  );
};

export default FestivalThemeBanner;
