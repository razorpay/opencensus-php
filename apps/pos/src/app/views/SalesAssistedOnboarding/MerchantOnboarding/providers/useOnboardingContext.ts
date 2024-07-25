/**
 * Note:
 * Handles no local state.
 * Handles on metadata/properties of the current onboarding instance
 *
 */

import { useNavigate, useParams } from 'react-router-dom';
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

export interface OnboardingValuesType {
  merchantId: string;
  isNewOnboarding: boolean;
  step: OnboardingStepType;
  component: string;
  onboardingSteps: OnboardingStep[];
}

export interface OnboardingStatesType {
  isModularLoading: boolean;
  isUpdateModularLoading: boolean;
  isModularFetchError: boolean;
}

export interface OnboardingHandlers {
  handleStepClick: ({ step }: { step: OnboardingStep }) => void;
  handleProceedToNextComponent: () => void;
  getStepConfigStepSlug: (step?: OnboardingStepType) => OnboardingStep | undefined;
  getFirstComponentOfStep: (step?: OnboardingStep) => OnboardingComponentType | undefined;
  getComponentConfigFromStep: (
    stepSlug?: OnboardingStepType,
    componentSlug?: OnboardingComponentType,
  ) => Component | undefined;
}

interface UseOnboardingContext {
  values: OnboardingValuesType;
  states: OnboardingStatesType;
  handlers: OnboardingHandlers;
}

export const availableSteps = Object.values(AvailableSteps as Record<string, OnboardingStepType>);

const useOnboardingContext = (): UseOnboardingContext => {
  const { id, step, component } = useParams();
  const merchantId = id === 'new' ? null : id;
  const navigate = useNavigate();

  const OnboardingValues: OnboardingValuesType = {
    component,
    isNewOnboarding: id === 'new',
    merchantId,
    step,
    onboardingSteps: ONBOARDING_STEPS,
  };

  const OnboardingStates: OnboardingStatesType = {
    isModularLoading: false,
    isUpdateModularLoading: false,
    isModularFetchError: false,
  };

  const handleStepClick = ({ step }: { step: OnboardingStep }) => {
    const { customOnClickHandler, slug } = step ?? {};
    customOnClickHandler ? customOnClickHandler() : navigate(slug as string);
  };

  const getStepConfigStepSlug = (stepSlug = step) => {
    if (!availableSteps.includes(stepSlug)) return;
    const onboardingStepConfig = ONBOARDING_STEPS.find(
      (stepConfig) => stepConfig.slug === stepSlug,
    );
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
      navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}`);
      return;
    }

    navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${nextComponent}`);
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
    },
  };
};

export default useOnboardingContext;
