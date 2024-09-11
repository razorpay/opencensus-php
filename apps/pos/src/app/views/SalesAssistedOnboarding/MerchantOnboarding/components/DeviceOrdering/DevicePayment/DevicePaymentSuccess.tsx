import React, { useEffect } from 'react';
import { ArrowRightIcon, Box, Button, Heading } from '@razorpay/blade/components';
import { PaymentSuccessContainer } from './styles';
import SuccessIcon from 'apps/pos/src/assets/paymentSuccess.svg';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import { PAGE_TYPES } from 'apps/pos/src/services/analytics/types';

interface DevicePaymentSuccessProps {
  handleGoToNextStep: () => void;
}

const DevicePaymentSuccess = ({ handleGoToNextStep }: DevicePaymentSuccessProps): JSX.Element => {
  useEffect(() => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
      action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
      properties: {
        pageType: PAGE_TYPES.ORDER_CONFIRMATION,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.POST_CHECKOUT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.ORDER_CONFIRMATION,
      },
    });
  }, []);

  const handleContinueToNextStep = (): void => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Continue Shopping',
        pageType: PAGE_TYPES.POST_CHECKOUT,
        section: 'Post checkout',
        subSection: 'Order Confirmation',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.POST_CHECKOUT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.ORDER_CONFIRMATION,
      },
    });
    handleGoToNextStep();
  };

  return (
    <PaymentSuccessContainer>
      <Box
        height="100%"
        display="flex"
        justifyContent="center"
        flexDirection="column"
        alignItems="center"
      >
        <Box marginBottom="spacing.5">
          <img src={SuccessIcon} alt="success-icon" height="100px" />
        </Box>
        <Heading
          weight="semibold"
          color="interactive.text.positive.normal"
          marginBottom="spacing.5"
          textAlign="center"
        >
          Order is successfully placed!
        </Heading>
        <Button icon={ArrowRightIcon} iconPosition="right" onClick={handleContinueToNextStep}>
          Continue to next step
        </Button>
      </Box>
    </PaymentSuccessContainer>
  );
};

export default DevicePaymentSuccess;
