import React, { useMemo } from 'react';
import DeviceDeliveryAddress from './DeviceDeliveryAddress';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';
import { getOrderSummaryFieldsFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';

const DeviceDeliveryAddressForSaleSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { isUpdateModularLoading, merchantDetails, modularConfig } = states;
  const {
    getComponentConfigFromStep,
    updateModularConfig,
    handleProceedToNextComponent,
    getStepConfigStepSlug,
  } = handlers;
  const componentConfig = getComponentConfigFromStep();
  const stepConfig = getStepConfigStepSlug();
  const deviceSummary = useMemo(() => {
    return getOrderSummaryFieldsFromModularConfig({ modularConfig });
  }, [modularConfig]);

  const { addedDevices = [], orderSummary } = deviceSummary ?? {};

  if (!componentConfig || !stepConfig?.modularKey || !merchantDetails || !orderSummary) return null;

  const isDeviceSelectionCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: stepConfig?.modularKey,
    }) === 'completed';

  return (
    <DeviceDeliveryAddress
      countryCode={modularConfig?.countryCode as string}
      addedDevices={addedDevices}
      orderSummary={orderSummary}
      title={componentConfig?.title as string}
      isUpdateModularLoading={isUpdateModularLoading}
      merchantDetails={merchantDetails}
      handleModularUpdate={updateModularConfig}
      handleGoToNextStep={handleProceedToNextComponent}
      isStepCompleted={isDeviceSelectionCompleted}
    />
  );
};

export default DeviceDeliveryAddressForSaleSalesAgent;
