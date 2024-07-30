import React from 'react';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from '../providers/useOnboardingContext';
import OnboardingHeader from 'apps/pos/src/app/components/OnboardingHeader';
import PageError from 'apps/pos/src/app/components/PageError/PageError';

const MerchantOnboardingComponent = (): JSX.Element => {
  const { states, handlers } = useOnboardingContext();
  const { isModularFetchError, isModularLoading } = states;
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

  if (isModularFetchError)
    return <PageError description="Failed to fetch modular config." isFullWidth={true} />;

  return (
    <React.Fragment>
      {componentConfig?.isFullScreenLayout ? null : (
        <Box margin="spacing.5">
          <OnboardingHeader isBackButtonVisible pageLabel={stepConfig?.title.toUpperCase()} />
        </Box>
      )}
      <Box
        flexGrow="1"
        backgroundColor="surface.background.gray.intense"
        paddingTop={componentConfig?.isFullScreenLayout ? 'spacing.5' : '0px'}
      >
        {isModularLoading ? 'Loading...' : component}
      </Box>
    </React.Fragment>
  );
};

export default MerchantOnboardingComponent;
