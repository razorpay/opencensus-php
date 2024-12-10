import React from 'react';
import {
  Button,
  Box,
  Heading,
  Text,
  ArrowRightIcon,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';
import PaymentLinkIcon from 'icons/merchant/payment-link.svg';
import CheckCircle2Icon from 'icons/merchant/check-circle-2.svg';
import RazorpayMeLinkIcon from 'icons/merchant/payout-link.svg';
import PaymentPagesLink from 'icons/merchant/payment-pages-2.svg';
import StorefrontIcon from 'icons/merchant/storefront.svg';
import InvoicesIcon from 'icons/merchant/invoices.svg';
import content from '../constants/content';
import { isMobileDevice } from 'merchant/components/Home/data';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { MonetizationChargesDetailsWrapper, NoCodeAppButton, NoCodeAppButtonIcon } from './styled';
import { User } from 'common/typings';
import getPricingPlan from '../utils/getPricingPlan';
import { getNoCodeMonetizationExperiment } from '../utils/getNoCodeMonetizationExperiment';

const getBannerRates = (pricingPlan: string | null) => {
  if (!pricingPlan) return [null, null];
  const price = Number(pricingPlan?.split('%')?.[0]);
  if (isNaN(price)) return [null, null];
  return [`${price + 1}%`, `${price + 1.5}%`];
};

interface MonetizationChargesDetailsProps {
  setShowCustomPricing: (show: boolean) => void;
  setShowProductWiseBenefits: (show: boolean) => void;
  closeModal: () => void;
  bannerKey?: string;
  screen: string;
  user?: User;
}

const MonetizationChargesDetails: React.FC<MonetizationChargesDetailsProps> = ({
  setShowCustomPricing,
  setShowProductWiseBenefits,
  closeModal,
  bannerKey = 'monetizationCharges',
  screen,
  user,
}) => {
  const { isDesktop } = useBladeBreakpoints();
  const { noCodeAppsBenefits } = content[bannerKey];
  const { title } = content[bannerKey]?.[screen];
  const isNoCodeMonetizationExperimentOn = getNoCodeMonetizationExperiment();
  const pricingPlanForMerchant = getPricingPlan(user, isNoCodeMonetizationExperimentOn);
  const [creditCardPlan, intlCreditCardPlan] = getBannerRates(pricingPlanForMerchant);
  const tooltipContent = `*Instruments like Diners and Amex Cards, International Cards, EMI (Credit Card, Debit Card & Cardless) & Corporate (Business) Credit Cards will be charged at ${creditCardPlan}. International Amex Cards will be charged at ${intlCreditCardPlan}.`;

  const handleCloseModal = () => {
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

  const showCustomPricingHandler = () => {
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
    setShowCustomPricing(true);
  };

  return (
    <MonetizationChargesDetailsWrapper>
      <Box display="flex" gap="12px" flexDirection="column">
        <Heading color="surface.text.gray.subtle" size="large" weight="semibold">
          Pricing for Links, Pages, Invoices
        </Heading>
      </Box>
      <Box
        display="flex"
        flexDirection={isDesktop ? 'row' : 'column'}
        margin={isDesktop ? ['24px', '0px'] : ['24px', '0px', '0px']}
        gap="16px"
        justifyContent="space-between"
        as="section"
      >
        <Box
          as="aside"
          display="flex"
          flexDirection="column"
          justifyContent="space-between"
          alignItems="flex-start"
          flex="1 0 0"
          alignSelf="stretch"
        >
          <Box display="inline">
            <Text color="surface.text.gray.subtle" size="small" weight="regular" display="inline">
              Transactions on Links, Pages, and Invoices incur a{' '}
              <Text color="surface.text.gray.normal" size="medium" weight="semibold" as="span">
                {pricingPlanForMerchant}
              </Text>{' '}
              fee, billed only upon successful payment
            </Text>
            <Box display="inline" marginLeft="spacing.2">
              <Tooltip content={tooltipContent} placement="right" zIndex={10001}>
                <TooltipInteractiveWrapper transform="translateY(2px)">
                  <InfoIcon size="small" color="surface.icon.gray.muted" />
                </TooltipInteractiveWrapper>
              </Tooltip>
            </Box>
          </Box>
          <Box>
            <Text
              color="surface.text.gray.muted"
              size="small"
              weight="medium"
              margin={['20px', '0px', '10px']}
            >
              Products applicable
            </Text>
            <Box display="flex" gap="10px" flexWrap="wrap">
              <NoCodeAppButton>
                <NoCodeAppButtonIcon src={PaymentLinkIcon} />
                <Text color="surface.text.gray.subtle" size="small" weight="medium">
                  Payment Links
                </Text>
              </NoCodeAppButton>
              <NoCodeAppButton>
                <NoCodeAppButtonIcon src={RazorpayMeLinkIcon} />
                <Text color="surface.text.gray.subtle" size="small" weight="medium">
                  Razorpay.me Link
                </Text>
              </NoCodeAppButton>
              <NoCodeAppButton>
                <NoCodeAppButtonIcon src={PaymentPagesLink} />
                <Text color="surface.text.gray.subtle" size="small" weight="medium">
                  Payment Pages
                </Text>
              </NoCodeAppButton>
              <NoCodeAppButton>
                <NoCodeAppButtonIcon src={StorefrontIcon} />
                <Text color="surface.text.gray.subtle" size="small" weight="medium">
                  Storefront Pages
                </Text>
              </NoCodeAppButton>
              <NoCodeAppButton>
                <NoCodeAppButtonIcon src={InvoicesIcon} />
                <Text color="surface.text.gray.subtle" size="small" weight="medium">
                  Invoices
                </Text>
              </NoCodeAppButton>
            </Box>
          </Box>
          <Text color="surface.text.gray.subtle" size="small" weight="regular" marginTop="20px">
            *18% GST Applicable
          </Text>
        </Box>
        <Box
          as="aside"
          flex="1"
          padding="24px"
          backgroundColor="surface.background.gray.moderate"
          borderRadius="large"
        >
          <Heading
            color="surface.text.gray.subtle"
            size="medium"
            weight="semibold"
            marginBottom="16px"
          >
            What you’ll get
          </Heading>
          <Box display="flex" flexDirection="column" gap="8px">
            {noCodeAppsBenefits.map((benefit, index) => (
              <Box display="flex" flexDirection="row" gap="6px" key={index}>
                <NoCodeAppButtonIcon src={CheckCircle2Icon} />
                <Box flex={1}>
                  <Text color="surface.text.gray.subtle" size="small" weight="regular">
                    {benefit}
                  </Text>
                </Box>
              </Box>
            ))}
          </Box>
          <span
            className="view-product-benefits-button"
            onClick={() => setShowProductWiseBenefits(true)}
          >
            View product-wise benefits
            <ArrowRightIcon
              color="interactive.icon.primary.normal"
              size="medium"
              marginLeft="4px"
            />
          </span>
        </Box>
      </Box>
      {isDesktop && <hr className="margin-0" />}
      <Box
        display="flex"
        gap={isDesktop ? '12px' : '16px'}
        justifyContent={isDesktop ? 'space-between' : 'center'}
        alignItems="center"
        width="100%"
        paddingTop="12px"
        flexDirection={isDesktop ? 'row' : 'column-reverse'}
      >
        <Text color="surface.text.gray.muted" size="small" weight="regular">
          Monthly revenue more than 5 lakh?{' '}
          <span className="contact-sales-button" onClick={showCustomPricingHandler}>
            Get Customised Pricing
          </span>
        </Text>
        <Button onClick={handleCloseModal} isFullWidth={!isDesktop}>
          Okay, got it
        </Button>
      </Box>
    </MonetizationChargesDetailsWrapper>
  );
};

export default MonetizationChargesDetails;
