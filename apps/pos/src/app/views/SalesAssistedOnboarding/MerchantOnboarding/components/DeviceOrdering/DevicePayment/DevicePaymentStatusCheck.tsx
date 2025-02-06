import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { Alert, Box, Button } from '@razorpay/blade/components';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DevicePaymentStatusCheckProps {
  isUpdateModularLoading: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DevicePaymentStatusCheck = ({
  isUpdateModularLoading,
  handleModularUpdate,
}: DevicePaymentStatusCheckProps): JSX.Element => {
  const [isClicked, setIsClicked] = useState(false);

  const onStatusCheckAttempt = () => {
    if (!isClicked) {
      setIsClicked(true);
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.IMAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          section: 'Checkout Status',
          subSection: 'Payment pending',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAYMENT_PENDING,
        },
      });
    }
  };

  const handlePaymentStatusCheck = () => {
    const payload: ModularPayload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_QR_PAYMENT_STATUS_CHECK]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: onStatusCheckAttempt,
    };
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Check Payment Status',
        section: 'Checkout Status',
        subSection: 'Checkout confirmation',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
      },
    });

    if (!isClicked) {
      // Refresh button clicked
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Refresh status',
          section: 'Checkout Status',
          subSection: 'Checkout confirmation',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        },
      });
    }

    handleModularUpdate(payload);
  };

  useEffect(() => {
    if (isUpdateModularLoading) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.IMAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          section: 'Checkout Status',
          subSection: 'Payment confirmation loading',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAYMENT_CONFIRMATION_LOADING,
        },
      });
    }
  }, [isUpdateModularLoading]);

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      padding="spacing.5"
      position="fixed"
      left="0px"
      right="0px"
      bottom="0px"
      elevation="highRaised"
      width="100%"
      zIndex={1}
    >
      {isClicked && !isUpdateModularLoading ? (
        <Alert
          color="notice"
          title="Payment Pending"
          description="Payment from the merchant hasn’t been intiated yet"
          marginBottom="spacing.5"
          isDismissible={false}
          isFullWidth
        />
      ) : null}
      <Button onClick={handlePaymentStatusCheck} isLoading={isUpdateModularLoading} isFullWidth>
        {isClicked ? 'Refresh' : 'Check Payment Status'}
      </Button>
    </Box>
  );
};

export default DevicePaymentStatusCheck;
