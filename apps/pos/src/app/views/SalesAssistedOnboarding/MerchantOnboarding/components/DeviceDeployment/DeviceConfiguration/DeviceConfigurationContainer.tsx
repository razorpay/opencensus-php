import React from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { Box } from '@razorpay/blade/components';
import DeviceConfiguration from './DeviceConfiguration';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getDeviceConfigurationDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const DeviceConfigurationContainer = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig, handleProceedToNextComponent } = handlers;
  if (!modularConfig) return null;

  const deviceConfigInfo = getDeviceConfigurationDetailsFromModularConfig({
    modularConfig,
  });
  if (!deviceConfigInfo) return null;

  const {
    deviceName,
    mappingStatus,
    language,
    hideLanguageSettings,
    hideWifiConfiguration,
    hideDeviceTesting,
    deviceId,
  } = deviceConfigInfo;

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
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
      <DeviceConfiguration
        deviceId={deviceId}
        handleUpdateModularConfig={updateModularConfig}
        handleProceed={handleProceedToNextComponent}
        isUpdateModularLoading={isUpdateModularLoading}
        deviceName={deviceName}
        mappingStatus={mappingStatus}
        language={language}
        hideLanguageSettings={hideLanguageSettings}
        hideWifiConfiguration={hideWifiConfiguration}
        hideDeviceTesting={hideDeviceTesting}
      />
    </ErrorBoundary>
  );
};

export default DeviceConfigurationContainer;
