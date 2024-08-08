import React from 'react';
import { ArrowLeftIconWrapper, CustomPricingWrapper } from './styled';
import { ArrowLeftIcon, Box, Text, Heading } from '@razorpay/blade/components';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import Image from 'common/ui/Image';
import GetCustomPricing from 'assets/pricing-bundle/get-custom-pricing.svg';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import content from '../constants/content';
import LeadFormLayout from './LeadFormLayout';
import { User } from 'common/typings';
import getPricingPlan from '../utils/getPricingPlan';
import LazyLoad from 'react-lazyload';

interface CustomPricingProps {
  closeModal: () => void;
  setShowCustomPricing: (show: boolean) => void;
  screen: string;
  user?: User;
  bannerKey: string;
}

const RightSideImageBanner: React.FC = () => {
  const { isDesktop } = useBladeBreakpoints();

  return isDesktop ? (
    <LazyLoad once>
      <Box alignSelf="stretch" maxWidth="384px" borderRadius="large">
        <Image
          src={GetCustomPricing}
          alt="get custom pricing for nocode apps"
          className="bg-size-cover"
        />
      </Box>
    </LazyLoad>
  ) : null;
};

const CustomPricing = ({
  closeModal,
  setShowCustomPricing,
  screen,
  user,
  bannerKey,
}: CustomPricingProps) => {
  const { title } = content[bannerKey][screen];
  const pricingPlanForMerchant = getPricingPlan(user);

  const sendAnalytics = () => {
    analyticsTrack({
      objectName: 'Customised Pricing Modal : Submit',
      actionName: 'Clicked',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.customised.pricing_modal.submit.click',
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
  };

  const handleClose = () => {
    sendAnalytics();
    closeModal();
  };

  return (
    <CustomPricingWrapper>
      <Box display="flex" justifyContent="space-between" gap="24px">
        <Box flex="1">
          <Box display="flex" flexDirection="row" gap="12px" alignItems="center">
            <ArrowLeftIconWrapper
              onClick={() => setShowCustomPricing(false)}
              data-testid="custom-pricing-back-button"
            >
              <ArrowLeftIcon color="interactive.icon.gray.muted" size="xlarge" />
            </ArrowLeftIconWrapper>
            <Heading color="surface.text.gray.subtle" size="large" weight="semibold">
              Get Customised Pricing
            </Heading>
          </Box>
          <Text color="surface.text.gray.muted" size="medium" weight="regular" margin="12px 0 24px">
            Monthly revenue over ₹5 lakh? Submit your details and we’ll contact you
          </Text>
          <Box display="flex" flexDirection="column" gap="24px">
            <LeadFormLayout formCampaignId="NoCode Apps Pricing Form" onClose={handleClose} />
          </Box>
        </Box>
        <RightSideImageBanner />
      </Box>
    </CustomPricingWrapper>
  );
};

export default CustomPricing;
