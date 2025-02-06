import React, { useMemo, useEffect, useContext } from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { Box } from '@razorpay/blade/components';
import DeviceDeliveryAddress from './DeviceDeliveryAddress';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers';
import { getProgressFromModularStep } from 'apps/pos/src/app/utils/modularConfig';
import { getOrderSummaryFieldsFromModularConfig } from 'apps/pos/src/app/utils/deviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { AvailableComponents, MODULES } from 'apps/pos/src/app/types/common';
import { SpiltzContext } from 'shell/SpiltzServiceContext';

const DeviceDeliveryAddressForSaleSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const splitz = useContext(SpiltzContext);
  const { isUpdateModularLoading, merchantDetails, modularConfig, isPosEkycAgent } = states;
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
  // if expt below is ON, then BE will send payment_options_component on address confirmation. Else, BE will continue sending qr_code_component
  const isBackendPLExptOn =
    splitz.abExperiments?.pos_payment_link_qr_comp?.variables?.result === 'on';

  const handleGoToNextStep = () => {
    if (orderSummary?.totalOrderCharge === 0) {
      //redirecting to order success page becoz order amount is 0
      handleProceedToNextComponent({
        __typeName: 'custom_routing',
        routerConditions: {
          [AvailableComponents.DEVICE_PAYMENT]: true,
        },
      });
    } else {
      if (isBackendPLExptOn) {
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.DEVICE_PAYMENT_METHODS]: true,
          },
        });
      } else {
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.DEVICE_PAYMENT]: true,
          },
        });
      }
    }
  };

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
        handleGoToNextStep={isPosEkycAgent ? handleProceedToNextComponent : handleGoToNextStep}
        isStepCompleted={isDeviceSelectionCompleted}
      />
    </ErrorBoundary>
  );
};

export default DeviceDeliveryAddressForSaleSalesAgent;
