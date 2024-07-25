import React from 'react';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from '../providers/useOnboardingContext';
import OnboardingHeader from 'apps/pos/src/app/components/OnboardingHeader';
import PageError from 'apps/pos/src/app/components/PageError/PageError';

const MerchantOnboardingComponent = (): JSX.Element => {
  const { handlers } = useOnboardingContext();
  const { getStepConfigStepSlug, getComponentConfigFromStep } = handlers;

  const stepConfig = getStepConfigStepSlug();
  const componentConfig = getComponentConfigFromStep();
  const component = componentConfig?.view;

  if (!stepConfig || !componentConfig || !component)
    return (
      <PageError
        description="Invalid step/component. Could not find correspoding configurations."
        isFullWidth={true}
      />
    );

  return (
    <React.Fragment>
      <Box margin="spacing.5">
        <OnboardingHeader isBackButtonVisible pageLabel={stepConfig?.title.toUpperCase()} />
      </Box>
      <Box flexGrow="1" backgroundColor="surface.background.gray.intense">
        {component}
      </Box>
    </React.Fragment>
  );
};

export default MerchantOnboardingComponent;
