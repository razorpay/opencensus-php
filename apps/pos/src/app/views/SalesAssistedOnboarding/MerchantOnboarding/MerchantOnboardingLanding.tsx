import React from 'react';

import { Box, ProgressBar, Text } from '@razorpay/blade/components';
import useOnboardingContext from './providers/useOnboardingContext';
import OnboardingStepCard from 'apps/pos/src/app/components/OnboardingStepCard/OnboardingStepCard';
import OnboardingHeader from 'apps/pos/src/app/components/OnboardingHeader';

const MerchantOnboardingLanding = (): JSX.Element => {
  const { values, states, handlers } = useOnboardingContext();
  const { onboardingSteps } = values;
  const { isModularLoading } = states;
  const { handleStepClick, getOnboardingProgress } = handlers;
  const { totalSteps, totalCompletedSteps } = getOnboardingProgress();

  return (
    <Box margin="spacing.5">
      <OnboardingHeader
        title="Merchant Onboarding"
        description="Get your merchants on board with the POS journey"
        footer={
          <ProgressBar
            label={`Step ${totalCompletedSteps} of ${totalSteps} Completed`}
            value={totalCompletedSteps}
            marginY="spacing.7"
            showPercentage={false}
            max={totalSteps}
            color={totalSteps === totalCompletedSteps ? 'positive' : undefined}
          />
        }
        isBackButtonVisible
      />
      {isModularLoading ? <Text marginY="spacing.5">Loading...</Text> : null}
      {onboardingSteps.map((step, index) => {
        const { slug, title, description, icon, getStatus, checkIfDisabled } = step;
        return (
          <OnboardingStepCard
            key={slug}
            slug={slug}
            title={`${index + 1}. ${title}`}
            description={description}
            icon={icon}
            status={getStatus({ values, states })}
            isDisabled={checkIfDisabled({ values, states })}
            onClick={() => handleStepClick({ step })}
          />
        );
      })}
    </Box>
  );
};

export { MerchantOnboardingLanding };
