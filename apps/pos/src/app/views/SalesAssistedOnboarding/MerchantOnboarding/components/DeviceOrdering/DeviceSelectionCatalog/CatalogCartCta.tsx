import React from 'react';
import { ArrowRightIcon, Badge, Box, Button, ShoppingCartIcon } from '@razorpay/blade/components';
import { DeviceOrderSummaryItem, ModularPayload } from 'apps/pos/src/app/types/modular';
import { MODULAR_FLAGS } from 'apps/pos/src/app/constants/DeviceSelection';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

interface CatalogCartCtaProps {
  addedDevices: DeviceOrderSummaryItem[];
  isUpdateModularLoading: boolean;
  isStepCompleted: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
  handleProceed: () => void;
}

const CatalogCartCta = ({
  addedDevices,
  isUpdateModularLoading,
  isStepCompleted,
  handleModularUpdate,
  handleProceed,
}: CatalogCartCtaProps): JSX.Element => {
  const addedDeviceCount = addedDevices.length;

  const handleOnProceedToCartClick = (): void => {
    if (!isStepCompleted) {
      const payload = {
        ...MODULAR_FLAGS.CONFIRM_DEVICE_SELECTION,
        [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: handleProceed,
      };
      handleModularUpdate?.(payload);
    } else {
      handleProceed();
    }
  };

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      padding="spacing.5"
      display="flex"
      position="fixed"
      left="0px"
      right="0px"
      bottom="0px"
      elevation="highRaised"
      alignItems="center"
      width="100%"
      zIndex={1}
    >
      <Box marginRight="spacing.7" position="relative">
        <ShoppingCartIcon color="interactive.icon.gray.muted" size="xlarge" />
        {addedDeviceCount ? (
          <Box position="absolute" left="12px" bottom="18px">
            <Badge color="primary" size="medium" emphasis="intense">
              {String(addedDeviceCount)}
            </Badge>
          </Box>
        ) : null}
      </Box>
      <Button
        variant="primary"
        icon={ArrowRightIcon}
        iconPosition="right"
        onClick={handleOnProceedToCartClick}
        isLoading={isUpdateModularLoading}
        isDisabled={addedDeviceCount === 0}
        isFullWidth
      >
        Proceed to cart
      </Button>
    </Box>
  );
};

export default CatalogCartCta;
