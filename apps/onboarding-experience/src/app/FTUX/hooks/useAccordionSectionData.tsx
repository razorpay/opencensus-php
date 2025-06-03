import React, { useCallback } from 'react';
import { Badge, DotIcon, Tooltip } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { AccordionDataType } from '@FTUX/types/homepage';
import { getAccordionWebsiteTitle } from '@FTUX/utils/homepage';
import AddWebsite from '@FTUX/components/AccordionSection/AddWebsite';
import PaymentGateway from '@FTUX/components/AccordionSection/PaymentGateway';
import AcceptTransactions from '@FTUX/components/AccordionSection/AcceptTransactions';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { hasAddedWebsite } from '@OnboardingExperienceCommons/utils/merchant';
import CollectPaymentsBannerImg from '@OnboardingExperienceAssets/CollectPaymentsBanner.svg';
import CollectPaymentsBannerAppsImg from '@OnboardingExperienceAssets/CollectPaymentsBannerApps.svg';
import { NO_CODE_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/constants/merchant';
import { hasAcceptedAnyPaymentChannel } from '@OnboardingExperienceCommons/utils/merchant';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';

const useAccordionSectionData = (): {
  activeStep: number;
  accordionData: AccordionDataType[];
  accordionHeaderImage: string;
} => {
  const { mode } = useStore((state) => state.session);
  const { merchantData } = useMerchantContext();
  const merchant = merchantData?.merchantById;

  const isMerchantActivated = Boolean(merchant?.activation?.isActivated);
  const hasWebsite = hasAddedWebsite(
    merchantData?.merchantById?.business?.paymentAcceptanceChannels,
  );
  const hasApiKeys = Boolean(merchant?.apiKeys?.[0]?.id);
  const hasTransacted = Boolean(merchant?.activation?.isTransacted);
  const paymentChannels = merchant?.business?.paymentAcceptanceChannels;

  // Calculate active step based on merchant status and activation type
  const determineActiveStep = (): number => {
    // Do not evaluate the website section for non-activated merchants
    if (!isMerchantActivated) {
      if (!hasApiKeys) return 0;
      if (!hasTransacted) return 1;
      return 2;
    }

    if (!hasWebsite) return 0;
    if (!hasApiKeys) return 1;
    if (!hasTransacted) return 2;
    return 3;
  };

  const activeStep = determineActiveStep();

  // Display pending badge when the website section is incomplete and collapsed
  const renderWebsiteStatusBadge = useCallback(
    (isExpanded: boolean): React.ReactNode => {
      if (!isExpanded && activeStep === 0) {
        return (
          <Badge color="information" size="medium">
            Pending
          </Badge>
        );
      }
      return null;
    },
    [activeStep],
  );

  // Display the current mode as badge when the accordion is expanded
  const renderPaymentGatewayStatusBadge = useCallback(
    (isExpanded: boolean): React.ReactNode => {
      if (!isExpanded) return null;

      return mode === 'test' ? (
        <Tooltip
          content="In test mode, you can test flows and features without real money before going live."
          placement="bottom"
        >
          <Badge color="notice" size="medium" icon={DotIcon}>
            You are in test mode
          </Badge>
        </Tooltip>
      ) : (
        <Badge color="positive" size="medium" icon={DotIcon}>
          You are in live mode
        </Badge>
      );
    },
    [mode],
  );

  // Define accordion data
  let accordionData: AccordionDataType[] = [
    {
      title: getAccordionWebsiteTitle(paymentChannels),
      getTitleSuffix: renderWebsiteStatusBadge,
      content: <AddWebsite />,
    },
    {
      title: 'Set up your payment gateway',
      getTitleSuffix: renderPaymentGatewayStatusBadge,
      content: <PaymentGateway />,
    },
    {
      title: 'Accept your first payment',
      content: <AcceptTransactions mode={mode} />,
    },
  ];

  // Do not display add website section for non-activated merchants
  accordionData = isMerchantActivated ? accordionData : accordionData.slice(1);

  // Determine merchant category based on their selected payment channels
  const isNoCodeMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, NO_CODE_CHANNEL_OPTIONS);
  const isWebsiteMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const isAppMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.IOS,
    PAYMENT_CHANNEL_OPTIONS.Android,
  ]);

  let accordionHeaderImage = CollectPaymentsBannerImg;

  if (isAppMerchant) {
    accordionHeaderImage = CollectPaymentsBannerAppsImg;
  }
  if (isWebsiteMerchant || isNoCodeMerchant) {
    accordionHeaderImage = CollectPaymentsBannerImg;
  }
  return {
    activeStep,
    accordionData,
    accordionHeaderImage,
  };
};

export default useAccordionSectionData;
