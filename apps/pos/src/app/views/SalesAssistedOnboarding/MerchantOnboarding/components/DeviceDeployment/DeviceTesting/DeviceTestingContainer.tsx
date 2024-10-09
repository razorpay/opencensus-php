import { Box } from '@razorpay/blade/components';
import errorService from '@razorpay/universe-cli/errorService';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import React from 'react';
import DeviceTesting from './DeviceTesting';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import { getTestingAmountDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';

const DeviceTestingContainer = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig, handleProceedToNextComponent } = handlers;

  if (!modularConfig) return null;

  const deviceTestingInfo = getTestingAmountDetailsFromModularConfig({
    modularConfig,
  });
  if (!deviceTestingInfo) return null;

  const { deviceName, description = '', defaultTestingAmount } = deviceTestingInfo;

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
      <DeviceTesting
        deviceName={deviceName}
        description={description}
        defaultTestingAmount={defaultTestingAmount}
        handleUpdateModularConfig={updateModularConfig}
        handleProceed={handleProceedToNextComponent}
        isUpdateModularLoading={isUpdateModularLoading}
      />
    </ErrorBoundary>
  );
};

export default DeviceTestingContainer;
