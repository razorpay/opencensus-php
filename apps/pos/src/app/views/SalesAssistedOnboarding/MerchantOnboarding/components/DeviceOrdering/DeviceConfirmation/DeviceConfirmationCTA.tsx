import React, { useState } from 'react';
import { Amount, Box, Button, Link, Text } from '@razorpay/blade/components';
import DeviceOrderPricing from './DeviceOrderPricing';
import ModalWithBottomSheet from 'apps/pos/src/app/components/ModalWithBottomSheet';
import { DeviceCharges } from 'apps/pos/src/app/types/modular';
import { OrderSummaryItemWithDeviceConfig } from 'apps/pos/src/app/types/DeviceSelection';

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

  if (!orderSummary) return null;

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
        <Link variant="button" onClick={() => setIsPricingDetailsOpen(true)}>
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
        onDismiss={() => setIsPricingDetailsOpen(false)}
        snapPoints={[0.8, 0.8, 0.8]}
      />
    </Box>
  );
};

export default DeviceConfirmationCTA;
