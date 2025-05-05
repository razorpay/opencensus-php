import React, { useCallback } from 'react';
import { Badge, DotIcon } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { AccordionDataType } from '@FTUX/types/homepage';
import { getAccordionWebsiteTitle } from '@FTUX/utils/homepage';
import AddWebsite from '@FTUX/Home/AccordionSection/AddWebsite';
import PaymentGateway from '@FTUX/Home/AccordionSection/PaymentGateway';
import AcceptTransactions from '@FTUX/Home/AccordionSection/AcceptTransactions';
import { useMerchantContext } from '@FTUX/context/MerchantContext';

const useAccordionSectionData = (): { activeStep: number; accordionData: AccordionDataType[] } => {
  const { mode, user: activeUser } = useStore((state) => state.session);
  const { merchantData } = useMerchantContext();
  const merchant = merchantData?.merchantById;

  const isMerchantActivated = Boolean(merchant?.activation?.isActivated);
  const hasWebsite = Boolean(activeUser.business_website);
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

      const isTestMode = mode === 'test';
      return (
        <Badge color={isTestMode ? 'notice' : 'positive'} size="medium" icon={DotIcon}>
          You are in {isTestMode ? 'test' : 'live'} mode
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
      content: <AcceptTransactions />,
    },
  ];

  // Do not display add website section for non-activated merchants
  accordionData = isMerchantActivated ? accordionData : accordionData.slice(1);

  return {
    activeStep,
    accordionData,
  };
};

export default useAccordionSectionData;
