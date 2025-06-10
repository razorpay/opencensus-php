import React, { useCallback, useMemo, useState } from 'react';
import { Badge, DotIcon, Tooltip } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { AccordionDataType } from '@FTUX/types/homepage';
import { getAccordionCompletedSteps, getAccordionWebsiteTitle } from '@FTUX/utils/homepage';
import AddWebsite from '@FTUX/components/AccordionSection/AddWebsite';
import PaymentGateway from '@FTUX/components/AccordionSection/PaymentGateway';
import AcceptTransactions from '@FTUX/components/AccordionSection/AcceptTransactions';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import CollectPaymentsBannerImg from '@OnboardingExperienceAssets/CollectPaymentsBanner.svg';
import CollectPaymentsBannerAppsImg from '@OnboardingExperienceAssets/CollectPaymentsBannerApps.svg';
import { NO_CODE_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/constants/merchant';
import { hasAcceptedAnyPaymentChannel } from '@OnboardingExperienceCommons/utils/merchant';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';

type UseAccordionSectionDataResponse = {
  expandedStep: number;
  setExpandedStep: (val: number) => void;
  completedSteps: number;
  accordionData: AccordionDataType[];
  isAppOnlyMerchant: boolean;
  accordionHeaderImage: string;
};

const useAccordionSectionData = (): UseAccordionSectionDataResponse => {
  const { mode } = useStore((state) => state.session);
  const { merchantData } = useMerchantContext();

  const merchant = merchantData?.merchantById;
  const isMerchantActivated = Boolean(merchant?.activation?.isActivated);
  const paymentChannels = merchant?.business?.paymentAcceptanceChannels;

  // Determine merchant category based on their selected payment channels
  const isNoCodeMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, NO_CODE_CHANNEL_OPTIONS);
  const isWebsiteMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const isAppMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Android,
    PAYMENT_CHANNEL_OPTIONS.IOS,
  ]);

  // Calculate completed step based on merchant status and activation type
  const completedSteps = useMemo(() => getAccordionCompletedSteps(merchant), [merchant]);

  // Initially open the accordion to the upcoming step
  const [expandedStep, setExpandedStep] = useState(completedSteps);

  // Display pending badge when the website section is incomplete and collapsed
  const renderWebsiteStatusBadge = useCallback(
    (isExpanded: boolean): React.ReactNode => {
      if (!isExpanded && completedSteps === 0) {
        return (
          <Badge color="information" size="medium">
            Pending
          </Badge>
        );
      }
      return null;
    },
    [completedSteps],
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

  // In case of add later, collapse the 1st step and expand the 2nd step (index 1)
  const handleAddLater = useCallback(() => {
    setExpandedStep(1);
  }, []);

  // Define accordion data
  let accordionData: AccordionDataType[] = [
    {
      title: getAccordionWebsiteTitle(paymentChannels),
      getTitleSuffix: renderWebsiteStatusBadge,
      content: <AddWebsite handleAddLater={handleAddLater} />,
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

  let accordionHeaderImage = CollectPaymentsBannerImg;

  if (isAppMerchant) {
    accordionHeaderImage = CollectPaymentsBannerAppsImg;
  }
  if (isWebsiteMerchant || isNoCodeMerchant) {
    accordionHeaderImage = CollectPaymentsBannerImg;
  }
  return {
    completedSteps,
    expandedStep,
    setExpandedStep,
    accordionData,
    isAppOnlyMerchant: isAppMerchant && !isWebsiteMerchant,
    accordionHeaderImage,
  };
};

export default useAccordionSectionData;
