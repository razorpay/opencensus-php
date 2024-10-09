/**
 * Note:
 * Handles no local state.
 * Handles on metadata/properties of the current onboarding instance
 *
 */

import { useNavigate, useParams } from 'react-router-dom';
import { ONBOARDING_STEPS_POS_EKYC } from '../MerchantOnboardingConfigPosEkyc';
import useMerchantActivation from './useMerchantActivation';
import useModular from './useModular';
import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import {
  Component,
  ONBOARDING_STEPS,
  OnboardingStep,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/MerchantOnboardingConfig';
import {
  AvailableSteps,
  OnboardingComponentType,
  OnboardingStepType,
} from 'apps/pos/src/app/types/common';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { MerchantDetails } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface UseOnboardingContextProps {
  onModularConfigUpdate?: (data: MerchantModularOnboardingDetailsSuccessResponse | null) => void;
  onMerchantDetailsFetchError?: () => void;
}

interface OnboardingProgress {
  totalSteps: number;
  totalCompletedSteps: number;
}

export interface OnboardingValuesType {
  merchantId: string;
  isNewOnboarding: boolean;
  step: OnboardingStepType;
  component: string;
  onboardingSteps: OnboardingStep[];
}

export interface OnboardingStatesType {
  isModularLoading: boolean;
  merchantDetails: MerchantDetails | undefined;
  updateModularConfig: (args: Record<string, unknown>) => void;
  isUpdateModularLoading: boolean;
  isModularFetchError: boolean;
  isRefetching: boolean;
  isPosEkycAgent: boolean;
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export interface OnboardingHandlers {
  handleStepClick: ({ step }: { step: OnboardingStep }) => void;
  handleProceedToNextComponent: () => void;
  getStepConfigStepSlug: (step?: OnboardingStepType) => OnboardingStep | undefined;
  getFirstComponentOfStep: (step?: OnboardingStep) => OnboardingComponentType | undefined;
  getOnboardingProgress: () => OnboardingProgress;
  getComponentConfigFromStep: (
    stepSlug?: OnboardingStepType,
    componentSlug?: OnboardingComponentType,
  ) => Component | undefined;
  updateModularConfig: (args: Record<string, unknown>) => void;
  refetchModularConfig: () => void;
}

interface UseOnboardingContext {
  values: OnboardingValuesType;
  states: OnboardingStatesType;
  handlers: OnboardingHandlers;
}

export const availableSteps = Object.values(AvailableSteps as Record<string, OnboardingStepType>);

const useOnboardingContext = ({
  onModularConfigUpdate,
  onMerchantDetailsFetchError,
}: UseOnboardingContextProps = {}): UseOnboardingContext => {
  const { id, step, component } = useParams();
  const merchantId: string = id === 'new' ? null : id;
  const navigate = useNavigate();
  const {
    isModularLoading,
    isRefetching,
    modularConfig,
    updateModularConfig,
    isUpdateModularLoading,
    isModularFetchError,
    refetchModular,
    isPosEkycAgent,
  } = useModular({ merchantId, onModularConfigUpdate });
  const { merchantDetails } = useMerchantActivation({
    merchantId,
    onMerchantDetailsFetchError,
  });
  const onboardingSteps = isPosEkycAgent ? ONBOARDING_STEPS_POS_EKYC : ONBOARDING_STEPS;

  const OnboardingValues: OnboardingValuesType = {
    component,
    isNewOnboarding: id === 'new',
    merchantId,
    step,
    onboardingSteps,
  };

  const OnboardingStates: OnboardingStatesType = {
    merchantDetails,
    isModularLoading,
    isUpdateModularLoading,
    isModularFetchError,
    isRefetching,
    modularConfig,
    isPosEkycAgent,
    updateModularConfig,
  };

  const trackStepClick = (step: OnboardingStep) => {
    if (step.modularKey === 'additional_details_step') {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_ONBOARDING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.ADDITIONAL_DETAILS,
          label: 'Step Clicked',
          section: 'Merchant Onboarding',
          subSection: 'Additional Details',
        },
      });
    }
  };

  const handleStepClick = ({ step }: { step: OnboardingStep }) => {
    trackStepClick(step);
    const { customOnClickHandler, slug } = step ?? {};
    customOnClickHandler ? customOnClickHandler() : navigate(slug as string);
  };

  const getStepConfigStepSlug = (stepSlug = step) => {
    if (!availableSteps.includes(stepSlug)) return;
    const onboardingStepConfig = onboardingSteps.find((stepConfig) => stepConfig.slug === stepSlug);
    return onboardingStepConfig;
  };

  const getFirstComponentOfStep = (stepConfig?: OnboardingStep) => {
    const step = stepConfig ?? getStepConfigStepSlug();
    if (!step) return;
    return step.components?.[0]?.slug;
  };

  const getComponentConfigFromStep = (stepSlug = step, componentSlug = component) => {
    const stepConfig = getStepConfigStepSlug(stepSlug);
    if (!stepConfig) return;

    const { components } = stepConfig;
    const componentConfig = components?.find(({ slug }) => slug === componentSlug);

    if (!componentConfig) return;
    return componentConfig;
  };

  const handleProceedToNextComponent = () => {
    const componentConfig = getComponentConfigFromStep();
    const nextComponent = componentConfig?.getNextComponent?.();

    if (!nextComponent) {
      navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}`);
      return;
    }

    navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${nextComponent}`);
  };

  const getOnboardingProgress = (): OnboardingProgress => {
    const totalSteps = ONBOARDING_STEPS.length;
    const totalCompletedSteps = ONBOARDING_STEPS.filter(
      ({ checkIfCompleted }) =>
        !!checkIfCompleted?.({ values: OnboardingValues, states: OnboardingStates }),
    );

    return {
      totalSteps,
      totalCompletedSteps: totalCompletedSteps.length,
    };
  };

  return {
    values: OnboardingValues,
    states: OnboardingStates,
    handlers: {
      handleStepClick,
      handleProceedToNextComponent,
      getStepConfigStepSlug,
      getFirstComponentOfStep,
      getComponentConfigFromStep,
      getOnboardingProgress,
      updateModularConfig,
      refetchModularConfig: refetchModular,
    },
  };
};

export default useOnboardingContext;
