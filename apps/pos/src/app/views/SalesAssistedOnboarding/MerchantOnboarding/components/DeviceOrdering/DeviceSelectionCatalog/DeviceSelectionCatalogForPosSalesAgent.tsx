import React from 'react';
import DeviceSelectionCatalog from './DeviceSelectionCatalog';
import { getCatalogDataFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';

const DeviceSelectionCatalogForPosSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
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
    <DeviceSelectionCatalog
      heading={componentConfig?.title ?? ''}
      deviceConfig={deviceConfig}
      addedDevices={addedDevices}
      isUpdateModularLoading={isUpdateModularLoading}
      handleProceed={handleProceedToNextComponent}
      handleModularUpdate={updateModularConfig}
      isStepCompleted={isDeviceSelectionCompleted}
    />
  );
};

export default DeviceSelectionCatalogForPosSalesAgent;
