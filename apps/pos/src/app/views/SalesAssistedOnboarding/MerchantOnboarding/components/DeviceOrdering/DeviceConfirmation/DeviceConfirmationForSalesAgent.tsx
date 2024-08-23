import React, { useMemo } from 'react';
import DeviceConfirmation from './DeviceConfirmation';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';
import { getOrderSummaryFieldsFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

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
    </ErrorBoundary>
  );
};

export default DeviceConfirmationForSalesAgent;
