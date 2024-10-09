import React from 'react';
import { getDeviceMappingDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { DeviceMappingSuccessComponent } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/DeviceMappingSuccess/DeviceMappingSuccessComponent';

const DeviceMappingScannerContainer = (): JSX.Element | null => {
  const { states } = useOnboardingContext();
  const { modularConfig } = states;

  if (!modularConfig) return null;
  const deviceDetails = getDeviceMappingDetailsFromModularConfig({
    modularConfig,
  });
  if (!deviceDetails) return null;

  const { details_page_name, device_serial, mapped_vpa } = deviceDetails;

  return (
    <DeviceMappingSuccessComponent
      deviceName={details_page_name}
      deviceSerialNumber={device_serial}
      mappedVpa={mapped_vpa}
    />
  );
};

export default DeviceMappingScannerContainer;
