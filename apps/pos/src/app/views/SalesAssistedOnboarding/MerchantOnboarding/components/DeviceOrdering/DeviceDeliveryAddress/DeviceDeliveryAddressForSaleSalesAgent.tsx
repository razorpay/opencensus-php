import React, { useMemo, useEffect } from 'react';
import DeviceDeliveryAddress from './DeviceDeliveryAddress';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';
import { getOrderSummaryFieldsFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import { Box } from '@razorpay/blade/components';

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

  useEffect(() => {
    if (componentConfig && stepConfig?.modularKey && merchantDetails && orderSummary) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          pageType: analyticsTypes.PAGE_TYPES.ORDER_DELIVERY_ADDRESS,
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_DELIVERY_ADDRESS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAGE_VIEW,
        },
      });
    }
  }, []);

  if (!componentConfig || !stepConfig?.modularKey || !merchantDetails || !orderSummary) return null;

  const isDeviceSelectionCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: stepConfig?.modularKey,
    }) === 'completed';

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.DEVICE_ADDRESS }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
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
    </ErrorBoundary>
  );
};

export default DeviceDeliveryAddressForSaleSalesAgent;
