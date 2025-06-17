import React from 'react';
import { ArrowLeftIconWrapper, NoCodeAppButtonIcon, ProductWiseBenefitsWrapper } from './styled';
import {
  Accordion,
  AccordionItem,
  AccordionItemBody,
  AccordionItemHeader,
  ArrowLeftIcon,
  Box,
  Button,
  Heading,
  Text,
} from '@razorpay/blade/components';
import { User } from 'common/typings';
import content from '../constants/content';
import { useBladeBreakpoints } from '@libs/shared-utils';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import getPricingPlan from '../utils/getPricingPlan';
import CheckCircle2Icon from 'icons/merchant/check-circle-2.svg';
import { getNoCodeMonetizationExperiment } from '../utils/getNoCodeMonetizationExperiment';

interface ProductWiseBenefitsProps {
  setShowProductWiseBenefits: (show: boolean) => void;
  closeModal: () => void;
  setShowCustomPricing: (show: boolean) => void;
  screen: string;
  user?: User;
  bannerKey: string;
}

const ProductWiseBenefits: React.FC<ProductWiseBenefitsProps> = ({
  setShowProductWiseBenefits,
  closeModal,
  setShowCustomPricing,
  screen,
  user,
  bannerKey,
}) => {
  const { title } = content[bannerKey][screen];
  const { noCodeApps } = content[bannerKey];
  const { isDesktop } = useBladeBreakpoints();
  const isNoCodeMonetizationExperimentOn = getNoCodeMonetizationExperiment();
  const pricingPlanForMerchant = getPricingPlan(user, isNoCodeMonetizationExperimentOn);

  const closeModalHandler = () => {
    analyticsTrack({
      objectName: 'Pricing Modal : Got it',
      actionName: 'Clicked',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.pricing_modal.got_it.click.initiated',
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

  const contactSales = () => {
    analyticsTrack({
      objectName: 'Pricing Modal : Contact Sales',
      actionName: 'Clicked',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.pricing_modal.contact_sales.click.inititated',
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
    analyticsTrack({
      objectName: 'Customised Pricing Modal',
      actionName: 'Render Success',
      screen: title,
      toCleverTap: true,
      properties: {
        event_name: 'nc_app.customised.pricing_modal.render.success',
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
    setShowCustomPricing(true);
  };

  return (
    <ProductWiseBenefitsWrapper>
      <Box display="flex" flexDirection="row" gap="12px" alignItems="center">
        <ArrowLeftIconWrapper onClick={() => setShowProductWiseBenefits(false)}>
          <ArrowLeftIcon color="interactive.icon.gray.muted" size="xlarge" />
        </ArrowLeftIconWrapper>
        <Heading color="surface.text.gray.subtle" size="large" weight="semibold">
          Product-wise Benefits
        </Heading>
      </Box>
      <Accordion variant="filled" size="medium" margin={['24px', '0px']}>
        {noCodeApps.map((appName, index) => (
          <AccordionItem key={index}>
            <AccordionItemHeader title={content.monetizationCharges[appName].title} />
            <AccordionItemBody>
              {content.monetizationCharges[appName].benefits.map((benefit, index) => (
                <Box display="flex" flexDirection="row" gap="6px" key={index}>
                  <NoCodeAppButtonIcon src={CheckCircle2Icon} />
                  {Boolean(typeof benefit === 'string') && (
                    <Box flex={1}>
                      <Text color="surface.text.gray.subtle" size="small" weight="regular">
                        {benefit}
                      </Text>
                    </Box>
                  )}
                  {Boolean(typeof benefit === 'object') && benefit?.boldAndLightStrikeThrough && (
                    <Box flex={1}>
                      <Text color="surface.text.gray.subtle" size="small" weight="regular">
                        {benefit.text}{' '}
                        <Text
                          as="span"
                          color="surface.text.gray.subtle"
                          size="small"
                          weight="medium"
                        >
                          {benefit.bold}
                        </Text>{' '}
                        <Text
                          as="span"
                          color="surface.text.gray.muted"
                          size="small"
                          weight="medium"
                          textDecorationLine="line-through"
                        >
                          {benefit.lightStrike}
                        </Text>
                      </Text>
                    </Box>
                  )}
                </Box>
              ))}
            </AccordionItemBody>
          </AccordionItem>
        ))}
      </Accordion>
      <Box
        display="flex"
        gap="12px"
        alignItems={isDesktop ? 'flex-end' : 'center'}
        width="100%"
        flexDirection="column"
      >
        <Button onClick={closeModalHandler} isFullWidth={!isDesktop}>
          Okay, got it
        </Button>
        {!isDesktop && (
          <Text color="surface.text.gray.subtle" size="small" weight="regular">
            Monthly revenue more than 5 lakh?{' '}
            <span className="contact-sales-button" onClick={contactSales}>
              Get Customised Pricing
            </span>
          </Text>
        )}
      </Box>
    </ProductWiseBenefitsWrapper>
  );
};

export default ProductWiseBenefits;
