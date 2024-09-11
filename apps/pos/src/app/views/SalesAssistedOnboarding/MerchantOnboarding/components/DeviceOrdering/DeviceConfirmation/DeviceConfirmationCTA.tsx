import React, { useState } from 'react';
import { useLocation } from 'react-router-dom';
import { Amount, Box, Button, Link, Text } from '@razorpay/blade/components';
import DeviceOrderPricing from './DeviceOrderPricing';
import ModalWithBottomSheet from 'apps/pos/src/app/components/ModalWithBottomSheet';
import { DeviceCharges } from 'apps/pos/src/app/types/modular';
import { OrderSummaryItemWithDeviceConfig } from 'apps/pos/src/app/types/DeviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DeviceConfirmationCTAProps {
  ctaName: string;
  extra?: JSX.Element;
  onCtaClick: () => void;
  isLoading?: boolean;
  isDisabled?: boolean;
  orderSummary: DeviceCharges;
  addedDevices: OrderSummaryItemWithDeviceConfig[];
}

const DeviceConfirmationCTA = ({
  ctaName,
  extra,
  onCtaClick,
  isDisabled,
  isLoading,
  addedDevices,
  orderSummary,
}: DeviceConfirmationCTAProps): JSX.Element | null => {
  const [isPricingDetailsOpen, setIsPricingDetailsOpen] = useState(false);
  const location = useLocation();

  if (!orderSummary) return null;

  const onViewDetailsClick = () => {
    setIsPricingDetailsOpen(true);

    // opened in delivery address page //
    if (location.pathname.includes('deviceDeliveryAddress')) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'View Details',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_DELIVERY_ADDRESS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.DELIVERY_ADDRESS_CONFIRMATION,
          section: 'Order Delivery Address',
          subSection: 'Delivery Address Confirmation',
        },
      });
    } else if (location.pathname.includes('deviceCart')) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'View Details',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_CONFIRMATION,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_CONFIRMATION,
          section: 'Order Confirmation',
          subSection: 'POS Product Confirmation',
        },
      });
    }
  };

  const onDismiss = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Close Icon',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_CONFIRMATION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAYMENT_DETAILS,
        section: 'Order Confirmation',
        subSection: 'Payment Details',
      },
    });
    setIsPricingDetailsOpen(false);
  };

  const onBottomsheetOpen = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
      action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
      properties: {
        pageType: analyticsTypes.PAGE_TYPES.PAYMENT_DETAILS,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_CONFIRMATION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAYMENT_DETAILS,
      },
    });
  };

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
      {extra}
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.5"
      >
        <Box>
          <Text color="surface.text.gray.subtle" marginBottom="spacing.2">
            Total Order Price
          </Text>
          <Amount
            value={orderSummary?.totalOrderCharge || 0}
            isAffixSubtle={false}
            suffix="none"
            weight="semibold"
          />
        </Box>
        <Link variant="button" onClick={onViewDetailsClick}>
          View Details
        </Link>
      </Box>
      <Button onClick={onCtaClick} isLoading={isLoading} isDisabled={isDisabled} isFullWidth>
        {ctaName}
      </Button>
      <ModalWithBottomSheet
        content={
          <DeviceOrderPricing addedDevices={addedDevices ?? []} orderSummary={orderSummary} />
        }
        isOpen={isPricingDetailsOpen}
        headerText="Payment Details"
        onDismiss={onDismiss}
        onBottomsheetOpen={onBottomsheetOpen}
        snapPoints={[0.8, 0.8, 0.8]}
      />
    </Box>
  );
};

export default DeviceConfirmationCTA;
