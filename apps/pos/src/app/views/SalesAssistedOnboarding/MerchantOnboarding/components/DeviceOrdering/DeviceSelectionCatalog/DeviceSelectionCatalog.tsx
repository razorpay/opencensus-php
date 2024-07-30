import React from 'react';
import { Box, Heading } from '@razorpay/blade/components';
import DeviceCard from './DeviceCard';
import CatalogCartCta from './CatalogCartCta';
import {
  DeviceConfig,
  DeviceOrderSummaryItem,
  ModularPayload,
} from 'apps/pos/src/app/types/modular';

interface DeviceSelectionCatalogProps {
  heading: string;
  deviceConfig: DeviceConfig[];
  addedDevices: DeviceOrderSummaryItem[];
  isUpdateModularLoading: boolean;
  isStepCompleted: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
  handleProceed: () => void;
}

const DeviceSelectionCatalog = ({
  heading,
  deviceConfig,
  addedDevices,
  isUpdateModularLoading,
  isStepCompleted,
  handleModularUpdate,
  handleProceed,
}: DeviceSelectionCatalogProps): JSX.Element | null => {
  return (
    <Box margin="spacing.5">
      <Heading marginBottom="spacing.5" size="large">
        {heading}
      </Heading>
      <Box display="flex" flexWrap="wrap">
        {deviceConfig.map((device) => (
          <DeviceCard
            key={device.title}
            deviceConfig={device}
            addedDevices={addedDevices}
            isUpdateModularLoading={isUpdateModularLoading}
            handleModularUpdate={handleModularUpdate}
            isDisabled={isStepCompleted}
          />
        ))}
      </Box>
      <CatalogCartCta
        addedDevices={addedDevices}
        isUpdateModularLoading={isUpdateModularLoading}
        handleModularUpdate={handleModularUpdate}
        handleProceed={handleProceed}
        isStepCompleted={isStepCompleted}
      />
    </Box>
  );
};

export default DeviceSelectionCatalog;
