import React, { useEffect, useState } from 'react';
import { Amount, Box, IconButton, Spinner, Text, TrashIcon } from '@razorpay/blade/components';
import AddDeviceToCart from '../DeviceSelectionCatalog/AddDeviceToCart';
import { DeviceConfig, ModularPayload } from 'apps/pos/src/app/types/modular';
import {
  MODULAR_DEVICE_FIELDS,
  OrderSummaryItemWithDeviceConfig,
} from 'apps/pos/src/app/types/DeviceSelection';
import {
  DEVICE_PLAN_TO_NAME_MAPPING,
  MODULAR_FLAGS,
} from 'apps/pos/src/app/constants/DeviceSelection';
import { getDeviceChargesFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DeviceOrderSummaryItemProps {
  isUpdateModularLoading: boolean;
  device: OrderSummaryItemWithDeviceConfig;
  deviceConfig: DeviceConfig | null;
  isDisabled?: boolean;
  isPosEkycAgent: boolean;
  handleUpdateModular: (payload: ModularPayload) => void;
}

const DeviceOrderSummaryItem = ({
  device,
  deviceConfig,
  isUpdateModularLoading,
  isDisabled,
  isPosEkycAgent,
  handleUpdateModular,
}: DeviceOrderSummaryItemProps): JSX.Element | null => {
  const [isItemLoading, setIsItemLoading] = useState(false);

  useEffect(() => {
    if (!isUpdateModularLoading) {
      setIsItemLoading(false);
    }
  }, [isUpdateModularLoading]);

  const handleDeleteProduct = (deviceId: string) => {
    setIsItemLoading(true);
    const payload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_CART_ID_FIELD]: deviceId,
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: () => setIsItemLoading(false),
      ...MODULAR_FLAGS.DELETE_CART_ITEM,
    };
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Delete Icon',
        pageType: analyticsTypes.PAGE_TYPES.ORDER_CONFIRMATION,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_CONFIRMATION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_CONFIRMATION,
        section: 'Cart',
        subSection: `${deviceConfig?.title} - Confirm order`,
      },
    });
    handleUpdateModular(payload);
  };

  const deviceFormDefaultValues = getDeviceChargesFromModularConfig({
    orderSummaryItem: device,
  });

  if (!deviceConfig) return null;

  return (
    <Box marginBottom="spacing.5">
      <Box
        display="flex"
        alignItems="center"
        marginBottom="spacing.5"
        justifyContent="space-between"
      >
        <Box display="flex" alignItems="center">
          <Box
            padding="spacing.3"
            borderRadius="medium"
            backgroundColor="surface.background.gray.subtle"
            marginRight="spacing.5"
            display="flex"
            alignItems="center"
            justifyContent="center"
            width="80px"
          >
            <img src={deviceConfig.icon as string} alt="deviceimage" height="60px" />
          </Box>
          <Box>
            <Text marginBottom="spacing.1" weight="semibold">
              {device?.deviceName}
            </Text>
            <Text color="surface.text.gray.muted">
              {DEVICE_PLAN_TO_NAME_MAPPING[device?.renewal ?? ''] ?? device?.renewal}
            </Text>
          </Box>
        </Box>
        {isItemLoading ? (
          <Spinner size="large" accessibilityLabel="delete spinner" />
        ) : (
          <IconButton
            icon={TrashIcon}
            size="large"
            onClick={() => handleDeleteProduct(device.itemId as string)}
            accessibilityLabel="delete device from cart"
            isDisabled={isDisabled}
          />
        )}
      </Box>
      <Box display="flex" alignItems="center" justifyContent="space-between">
        <Box>
          <Text color="surface.text.gray.muted" marginBottom="spacing.2">
            Qty: {device.quantity}
          </Text>
          {!isNaN(device?.totalSetupCharge as number) ? (
            <Amount
              value={Number(device?.totalSetupCharge)}
              suffix="decimals"
              weight="semibold"
              isAffixSubtle={false}
            />
          ) : null}
        </Box>
        <AddDeviceToCart
          isPosEkycAgent={isPosEkycAgent}
          deviceConfig={deviceConfig}
          defaultValues={deviceFormDefaultValues}
          isUpdateModularLoading={isUpdateModularLoading}
          handleModularUpdate={handleUpdateModular}
          isDisabled={isDisabled}
          isEditFlow
        />
      </Box>
    </Box>
  );
};

export default DeviceOrderSummaryItem;
