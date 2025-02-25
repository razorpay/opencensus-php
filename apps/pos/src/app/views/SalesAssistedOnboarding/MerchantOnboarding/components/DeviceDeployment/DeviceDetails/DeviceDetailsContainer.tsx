import { Box } from '@razorpay/blade/components';
import errorService from '@razorpay/universe-cli/errorService';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import React from 'react';
import DeviceDetails from './DeviceDetails';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import { getDeviceConfigurationDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';

const DeviceDetailsContainer = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;
  if (!modularConfig) return null;

  const deviceDetails = getDeviceConfigurationDetailsFromModularConfig({
    modularConfig,
  });
  if (!deviceDetails) return null;

  const {
    mappingStatus,
    language = 'English',
    hideLanguageSettings,
    hideWifiConfiguration,
    currentDeviceDetails,
    deviceId,
  } = deviceDetails;

  return (
    <ErrorBoundary
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.DEVICE_DEPLOYMENT }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <DeviceDetails
        deviceId={deviceId}
        mappingStatus={mappingStatus}
        isLoading={isUpdateModularLoading}
        updateModularConfig={updateModularConfig}
        currentDeviceDetails={currentDeviceDetails}
        language={language}
        hideLanguageSettings={hideLanguageSettings}
        hideWifiConfiguration={hideWifiConfiguration}
      />
    </ErrorBoundary>
  );
};

export default DeviceDetailsContainer;
