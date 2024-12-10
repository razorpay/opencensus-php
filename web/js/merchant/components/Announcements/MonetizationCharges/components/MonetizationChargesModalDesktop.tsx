import React from 'react';
import { Modal, ModalBody, ModalHeader } from '@razorpay/blade/components';
import content from '../constants/content';
import CustomPricing from './CustomPricing';
import ProductWiseBenefits from './ProductWiseBenefits';
import { isMobileDevice } from 'merchant/components/Home/data';
import MonetizationChargesDetails from './MonetizationChargesDetails';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { User } from 'common/typings';
import getPricingPlan from '../utils/getPricingPlan';
import { getNoCodeMonetizationExperiment } from '../utils/getNoCodeMonetizationExperiment';

interface MonetizationChargesModalDesktopProps {
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

const MonetizationChargesModalDesktop: React.FC<MonetizationChargesModalDesktopProps> = ({
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
  const isNoCodeMonetizationExperimentOn = getNoCodeMonetizationExperiment();
  const pricingPlanForMerchant = getPricingPlan(user, isNoCodeMonetizationExperimentOn);
  const closeModal = () => {
    setShowProductWiseBenefits(false);
    setShowCustomPricing(false);
    setIsOpen(false);
  };

  const modalCloseHandler = () => {
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
    closeModal();
  };

  return (
    <Modal
      isOpen={isOpen}
      onDismiss={modalCloseHandler}
      size={showCustomPricing ? 'large' : 'medium'}
    >
      <ModalHeader />
      <ModalBody>
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
            setShowCustomPricing={setShowCustomPricing}
            closeModal={closeModal}
            screen={screen}
            user={user}
            bannerKey={bannerKey}
          />
        )}
      </ModalBody>
    </Modal>
  );
};

export default MonetizationChargesModalDesktop;
