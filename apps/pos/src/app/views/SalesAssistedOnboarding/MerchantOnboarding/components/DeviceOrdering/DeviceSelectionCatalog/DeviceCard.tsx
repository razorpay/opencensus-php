import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import AddDeviceToCart from './AddDeviceToCart';
import {
  DeviceConfig,
  DeviceOrderSummaryItem,
  ModularPayload,
} from 'apps/pos/src/app/types/modular';

interface DeviceCardProps {
  deviceConfig: DeviceConfig;
  isUpdateModularLoading: boolean;
  addedDevices: DeviceOrderSummaryItem[];
  isDisabled?: boolean;
  isPosEkycAgent: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DeviceCard = ({
  deviceConfig,
  addedDevices,
  isUpdateModularLoading,
  isDisabled,
  isPosEkycAgent,
  handleModularUpdate,
}: DeviceCardProps): JSX.Element => {
  const addedDevice =
    addedDevices.find(({ deviceName }) => deviceName === deviceConfig.title) ?? null;
  return (
    <Box
      padding="spacing.5"
      borderWidth="thick"
      borderColor="surface.border.gray.muted"
      backgroundColor="surface.background.gray.intense"
      marginRight={{ base: 'spacing.0', l: 'spacing.5' }}
      marginBottom="spacing.5"
      borderRadius="medium"
      minWidth={{ base: '100%', l: '350px' }}
    >
      <Box
        backgroundColor="surface.background.gray.subtle"
        padding="spacing.3"
        display="flex"
        alignItems="center"
        justifyContent="center"
        marginBottom="spacing.5"
      >
        <img src={deviceConfig.icon as string} alt="device icon" height="140px" />
      </Box>
      <Box
        display={{ base: 'flex', l: 'block' }}
        justifyContent="space-between"
        alignItems="center"
      >
        <Text size="large" weight="semibold" marginBottom={{ base: 'spacing.0', l: 'spacing.5' }}>
          {deviceConfig.title}
        </Text>
        <AddDeviceToCart
          isPosEkycAgent={isPosEkycAgent}
          deviceConfig={deviceConfig}
          isDeviceAlreadyAdded={!!addedDevice}
          isUpdateModularLoading={isUpdateModularLoading}
          handleModularUpdate={handleModularUpdate}
          isDisabled={isDisabled}
        />
      </Box>
    </Box>
  );
};

export default DeviceCard;
