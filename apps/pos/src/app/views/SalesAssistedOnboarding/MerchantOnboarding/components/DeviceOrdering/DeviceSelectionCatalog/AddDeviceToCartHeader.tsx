import React from 'react';
import {
  Box,
  Heading,
  IconButton,
  MinusIcon,
  PlusIcon,
  Spinner,
  TrashIcon,
} from '@razorpay/blade/components';
import { useController, useFormContext } from 'react-hook-form';
import { DeviceConfig } from 'apps/pos/src/app/types/modular';
import { MODULAR_DEVICE_FIELDS, QuantityActions } from 'apps/pos/src/app/types/DeviceSelection';
import { updateQuantity } from 'apps/pos/src/app/utils/deviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface AddDeviceToCartHeaderProps {
  deviceConfig: DeviceConfig;
  isEditFlow: boolean;
  onDelete: () => void;
  isLoading?: boolean;
}

const AddDeviceToCartHeader = ({
  deviceConfig,
  isEditFlow,
  onDelete,
  isLoading,
}: AddDeviceToCartHeaderProps): JSX.Element => {
  const { control, setValue } = useFormContext();
  const { field: quantityField } = useController({
    name: MODULAR_DEVICE_FIELDS.DEVICE_QUANTITY,
    control,
  });

  const handleOnQuantiyActionClick = (action: QuantityActions): void => {
    const newQuantity = updateQuantity({
      currentQuantity: quantityField.value,
      action,
    });

    if (action === QuantityActions.add) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          type: 'Add',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_EDITING,
          section: 'Pre Checkout',
          subSection: deviceConfig.title,
          noOfItems: quantityField.value,
        },
      });
    } else {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          type: 'Remove',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_EDITING,
          subSection: deviceConfig.title,
          section: 'Pre Checkout',
          noOfItems: quantityField.value,
        },
      });
    }

    setValue(MODULAR_DEVICE_FIELDS.DEVICE_QUANTITY, newQuantity);
  };

  return (
    <Box marginBottom="spacing.5">
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.5"
      >
        <Box display="flex" alignItems="center">
          <Box
            padding="spacing.3"
            borderRadius="medium"
            backgroundColor="surface.background.gray.subtle"
            borderWidth="thin"
            marginRight="spacing.5"
            borderColor="surface.border.gray.subtle"
            display="flex"
            alignItems="center"
            justifyContent="center"
            width="60px"
          >
            <img src={deviceConfig?.icon as string} alt="deviceimage" height="40px" />
          </Box>
          <Heading>{deviceConfig.title}</Heading>
        </Box>
        {isLoading ? <Spinner size="large" accessibilityLabel="modular loading" /> : null}
        {isEditFlow && !isLoading ? (
          <IconButton
            icon={TrashIcon}
            size="large"
            onClick={onDelete}
            accessibilityLabel="delete device item"
          />
        ) : null}
      </Box>
      <Box
        borderRadius="medium"
        borderWidth="thin"
        borderColor="surface.border.gray.subtle"
        padding="spacing.2"
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        maxWidth="80px"
        testID="device-quantity-field"
      >
        <IconButton
          icon={MinusIcon}
          onClick={() => handleOnQuantiyActionClick(QuantityActions.reduce)}
          accessibilityLabel="reduce quantity"
          isDisabled={quantityField.value === 1 || isLoading}
        />
        <Heading color="surface.text.gray.subtle" testID="device-quantity-text">
          {quantityField.value}
        </Heading>
        <IconButton
          icon={PlusIcon}
          onClick={() => handleOnQuantiyActionClick(QuantityActions.add)}
          accessibilityLabel="increase quantity"
          isDisabled={isLoading}
        />
      </Box>
    </Box>
  );
};

export default AddDeviceToCartHeader;
