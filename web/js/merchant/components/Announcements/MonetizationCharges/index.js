import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import content from './constants/content';
import { Box, ToastContainer } from '@razorpay/blade/components';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { MonetizationChargesWrapper } from './components/styled';
import MonetizationChargesModalDesktop from './components/MonetizationChargesModalDesktop';
import MonetizationChargesModalMobile from './components/MonetizationChargesModalMobile';
import getPricingPlan from './utils/getPricingPlan';
import { getNoCodeMonetizationExperiment } from './utils/getNoCodeMonetizationExperiment';
import { getItem, setItem } from 'common/utils/localStorage';

const MonetizationChargesBanner = ({
  bannerKey = 'monetizationCharges',
  userId = '',
  screen,
  user = {},
}) => {
  const { bannerId } = content[bannerKey];
  const { bannerText, title } = content[bannerKey][screen];
  const [isOpen, setIsOpen] = useState(false);
  const { isDesktop } = useBladeBreakpoints();
  const [showProductWiseBenefits, setShowProductWiseBenefits] = useState(false);
  const [showCustomPricing, setShowCustomPricing] = useState(false);
  const [hidden, setHidden] = useState(() => {
    return (
      sessionStorage.getItem(`${bannerKey}-${screen}-hidden`) === 'true' ||
      Number(getItem(`${bannerKey}-${screen}-closeCount`)) >= 4
    );
  });
  const pricingPlanForMerchant = getPricingPlan(user);
  const isNoCodeMonetizationExperimentOn = getNoCodeMonetizationExperiment();
  const showBanner =
    (user?.isNocodeappFeeApplicable || isNoCodeMonetizationExperimentOn) && pricingPlanForMerchant;

  const openModal = () => {
    analyticsTrack({
      objectName: 'Pricing Banner : View Pricing',
      actionName: 'Clicked',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.pricing_banner.view_pricing.click.initiated',
        source: getDeviceSource(),
        page: title,
        email_id: user.email,
        url: window.location.href,
        browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
        activation_status: user.activation_status,
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        exp_name: 'NoCode Monetization',
        pricing: pricingPlanForMerchant,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    analyticsTrack({
      objectName: 'Pricing Modal',
      actionName: 'Render Success',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.pricing_modal.render.success',
        source: getDeviceSource(),
        page: title,
        email_id: user.email,
        url: window.location.href,
        browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
        activation_status: user.activation_status,
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        exp_name: 'NoCode Monetization',
        pricing: pricingPlanForMerchant,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    setIsOpen(!isOpen);
  };

  const sendAnalyticsEvent = () => {
    analyticsTrack({
      objectName: 'Pricing Banner : Close',
      actionName: 'Clicked',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.pricing_banner.close.click.initiated',
        source: getDeviceSource(),
        page: title,
        email_id: user.email,
        url: window.location.href,
        browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
        activation_status: user.activation_status,
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        exp_name: 'NoCode Monetization',
        pricing: pricingPlanForMerchant,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const closeCount = Number(getItem(`${bannerKey}-${screen}-closeCount`)) || 0;
    const newCloseCount = closeCount + 1;
    setItem(`${bannerKey}-${screen}-closeCount`, newCloseCount);
    sessionStorage.setItem(`${bannerKey}-${screen}-hidden`, 'true');
    setHidden(true);
  };

  useEffect(() => {
    if (showBanner && !hidden) {
      analyticsTrack({
        objectName: 'Pricing Banner',
        actionName: 'Render Success',
        screen: title,
        toCleverTap: true,
        properties: {
          event_name: 'nc_app.pricing_banner.success',
          source: getDeviceSource(),
          page: title,
          email_id: user.email,
          url: window.location.href,
          browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
          activation_status: user.activation_status,
          device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
          exp_name: 'NoCode Monetization',
          pricing: pricingPlanForMerchant,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, []);

  return showBanner ? (
    <MonetizationChargesWrapper>
      <ToastContainer />
      <AnnouncementBanner
        title="Charges"
        card_id={`${bannerId}-${userId}`}
        canBeClosed={true}
        theme="primary"
        handleClose={sendAnalyticsEvent}
        hidden={hidden}
      >
        <Box>
          <span>{bannerText}</span>
          {isDesktop && (
            <>
              <span className="separator" />
              <Link className="btn-link" onClick={openModal}>
                <strong>View Pricing</strong>
              </Link>
            </>
          )}
        </Box>
        {!isDesktop && (
          <Box marginTop="4px">
            <Link className="btn-link" onClick={openModal}>
              <strong>View Pricing</strong>
            </Link>
          </Box>
        )}
      </AnnouncementBanner>
      {isDesktop ? (
        <MonetizationChargesModalDesktop
          isOpen={isOpen}
          setIsOpen={setIsOpen}
          showProductWiseBenefits={showProductWiseBenefits}
          setShowProductWiseBenefits={setShowProductWiseBenefits}
          showCustomPricing={showCustomPricing}
          setShowCustomPricing={setShowCustomPricing}
          screen={screen}
          user={user}
          bannerKey={bannerKey}
        />
      ) : (
        <MonetizationChargesModalMobile
          isOpen={isOpen}
          setIsOpen={setIsOpen}
          showProductWiseBenefits={showProductWiseBenefits}
          setShowProductWiseBenefits={setShowProductWiseBenefits}
          showCustomPricing={showCustomPricing}
          setShowCustomPricing={setShowCustomPricing}
          screen={screen}
          user={user}
          bannerKey={bannerKey}
        />
      )}
    </MonetizationChargesWrapper>
  ) : null;
};

export default React.memo(MonetizationChargesBanner);
