import React from 'react';
import content from '../constants/content';
import CustomPricing from './CustomPricing';
import ProductWiseBenefits from './ProductWiseBenefits';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import MonetizationChargesDetails from './MonetizationChargesDetails';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { BottomSheet, BottomSheetBody, BottomSheetHeader, Box } from '@razorpay/blade/components';
import { User } from 'common/typings';
import getPricingPlan from '../utils/getPricingPlan';

interface MonetizationChargesModalMobileProps {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  showProductWiseBenefits: boolean;
  setShowProductWiseBenefits: (show: boolean) => void;
  showCustomPricing: boolean;
  setShowCustomPricing: (show: boolean) => void;
  screen: string;
  user?: User;
  bannerKey: string;
}

const MonetizationChargesModalMobile: React.FC<MonetizationChargesModalMobileProps> = ({
  isOpen,
  setIsOpen,
  showProductWiseBenefits,
  setShowProductWiseBenefits,
  showCustomPricing,
  setShowCustomPricing,
  screen,
  user,
  bannerKey,
}) => {
  const { title } = content[bannerKey][screen];
  const pricingPlanForMerchant = getPricingPlan(user);

  const closeModal = () => {
    analyticsTrack({
      objectName: 'Pricing Modal : Close',
      actionName: 'Clicked',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.pricing_modal.close.click.initiated',
        source: getDeviceSource(),
        page: title,
        email_id: user?.email,
        url: window.location.href,
        browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
        activation_status: user?.activation_status,
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        exp_name: 'NoCode Monetization',
        pricing: pricingPlanForMerchant,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    setShowProductWiseBenefits(false);
    setShowCustomPricing(false);
    setIsOpen(false);
  };

  return (
    <Box>
      <BottomSheet
        isOpen={isOpen}
        onDismiss={closeModal}
        snapPoints={[0.65, 0.8, 1]}
        zIndex={10000}
      >
        <BottomSheetHeader />
        <BottomSheetBody>
          {showProductWiseBenefits && (
            <ProductWiseBenefits
              closeModal={closeModal}
              setShowProductWiseBenefits={setShowProductWiseBenefits}
              setShowCustomPricing={setShowCustomPricing}
              screen={screen}
              user={user}
              bannerKey={bannerKey}
            />
          )}
          {showCustomPricing && (
            <CustomPricing
              closeModal={closeModal}
              setShowCustomPricing={setShowCustomPricing}
              screen={screen}
              user={user}
              bannerKey={bannerKey}
            />
          )}
          {!showProductWiseBenefits && !showCustomPricing && (
            <MonetizationChargesDetails
              closeModal={closeModal}
              setShowCustomPricing={setShowCustomPricing}
              setShowProductWiseBenefits={setShowProductWiseBenefits}
              screen={screen}
              user={user}
              bannerKey={bannerKey}
            />
          )}
        </BottomSheetBody>
      </BottomSheet>
    </Box>
  );
};

export default MonetizationChargesModalMobile;
