import React, { useEffect } from 'react';
import { ArrowRightIcon, Box, Button, Text } from '@razorpay/blade/components';
import desktopBackground from 'assets/diwali-banner/desktop-background.svg';
import mobileBackground from 'assets/diwali-banner/mobile-background.svg';
import { connect } from 'react-redux';

import { useMobile } from 'common/hooks/useMobile';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

const track = (actionName) =>
  analyticsTrackWithUserInfo({
    objectName: 'diwali report banner',
    actionName,
    screen: 'homepage',
    addUserProperties: true,
  });

const DiwaliReportBanner = ({ user, isRtux = false, isMobileView = false }) => {
  const isMobile = useMobile();
  const splitz = useSplitzService();
  const shouldShowBanner =
    isExperimentEnabled(splitz?.abExperiments?.diwali_report_banner) &&
    user?.isCountryIndia &&
    user?.isOrgRZP &&
    !user.isFeatureEnabled('raas');

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
          See how&nbsp;
          <Text color="surface.text.onSea.onIntense" size="large" display="inline">
            India shopped
          </Text>
          &nbsp;this Diwali! 🧨
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
            window.open('https://online.fliphtml5.com/tatrf/tlkr/', '_blank');
          }}
        >
          View Diwali Report
        </Button>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(DiwaliReportBanner);
