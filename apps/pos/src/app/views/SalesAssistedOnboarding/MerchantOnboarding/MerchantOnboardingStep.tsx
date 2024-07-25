import React from 'react';
import { Navigate } from 'react-router-dom';

import PageError from 'apps/pos/src/app/components/PageError';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';

const MerchantOnboardingStep = (): JSX.Element => {
  const { values, handlers } = useOnboardingContext();
  const { step } = values;
  const { getStepConfigStepSlug, getFirstComponentOfStep } = handlers;

  const stepConfig = getStepConfigStepSlug(step);

  if (!stepConfig)
    return <PageError description="Invalid step. Could not find step config." isFullWidth={true} />;

  const componentSlug = getFirstComponentOfStep();
  return <Navigate to={componentSlug} replace />;
};

export default MerchantOnboardingStep;
