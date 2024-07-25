import React from 'react';

import { Box, ProgressBar } from '@razorpay/blade/components';
import useOnboardingContext from './providers/useOnboardingContext';
import OnboardingStepCard from 'apps/pos/src/app/components/OnboardingStepCard/OnboardingStepCard';
import OnboardingHeader from 'apps/pos/src/app/components/OnboardingHeader';

const MerchantOnboardingLanding = (): JSX.Element => {
  const { values, states, handlers } = useOnboardingContext();
  const { onboardingSteps } = values;
  const { handleStepClick } = handlers;

  return (
    <Box margin="spacing.5">
      <OnboardingHeader
        title="Merchant Onboarding"
        description="Get your merchants on board with the POS journey"
        footer={
          <ProgressBar
            label="Step 1 of 6 Completed"
            value={30}
            marginY="spacing.7"
            showPercentage={false}
          />
        }
        isBackButtonVisible
      />
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
