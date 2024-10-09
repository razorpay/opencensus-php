import React from 'react';
import { Box } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import DeviceSelectionCatalog from './DeviceSelectionCatalog';
import { getCatalogDataFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const DeviceSelectionCatalogForPosSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading, isPosEkycAgent } = states;
  const {
    getComponentConfigFromStep,
    updateModularConfig,
    handleProceedToNextComponent,
    getStepConfigStepSlug,
  } = handlers;

  if (!modularConfig) return null;

  const { deviceConfig, addedDevices } = getCatalogDataFromModularConfig({ modularConfig });
  const componentConfig = getComponentConfigFromStep();
  const stepConfig = getStepConfigStepSlug();

  if (!deviceConfig || !componentConfig?.modularKey || !stepConfig?.modularKey) return null;

  const isDeviceSelectionCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: stepConfig?.modularKey,
    }) === 'completed';

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.DEVICE_SELECTION }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <DeviceSelectionCatalog
        isPosEkycAgent={isPosEkycAgent}
        heading={componentConfig?.title ?? ''}
        deviceConfig={deviceConfig}
        addedDevices={addedDevices}
        isUpdateModularLoading={isUpdateModularLoading}
        handleProceed={handleProceedToNextComponent}
        handleModularUpdate={updateModularConfig}
        isStepCompleted={isDeviceSelectionCompleted}
      />
    </ErrorBoundary>
  );
};

export default DeviceSelectionCatalogForPosSalesAgent;
