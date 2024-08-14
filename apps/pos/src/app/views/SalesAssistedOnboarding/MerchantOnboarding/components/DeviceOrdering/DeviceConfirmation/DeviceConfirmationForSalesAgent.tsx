import React, { useMemo } from 'react';
import DeviceConfirmation from './DeviceConfirmation';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';
import { getOrderSummaryFieldsFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';

const DeviceConfirmationForSalesAgent = (): JSX.Element | null => {
  const { states, values, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { merchantId } = values;
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

  const {
    addedDevices = [],
    orderSummary,
    customPricingDocuments = [],
    isCustomRatesApplicable,
  } = deviceSummary ?? {};

  if (!componentConfig || !modularConfig || !stepConfig?.modularKey || !orderSummary) return null;

  const isDeviceSelectionCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: stepConfig?.modularKey,
    }) === 'completed';

  return (
    <DeviceConfirmation
      addedDevices={addedDevices}
      orderSummary={orderSummary}
      customPricingDocuments={customPricingDocuments}
      title={componentConfig?.title as string}
      merchantId={merchantId}
      isUpdateModularLoading={isUpdateModularLoading}
      handleUpdateModular={updateModularConfig}
      handleGoToNextStep={handleProceedToNextComponent}
      isCustomRatesApplicable={!!isCustomRatesApplicable}
      isDisabled={isDeviceSelectionCompleted}
    />
  );
};

export default DeviceConfirmationForSalesAgent;
