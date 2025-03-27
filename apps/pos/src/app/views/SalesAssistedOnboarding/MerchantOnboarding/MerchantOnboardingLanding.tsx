import React from 'react';

import { Box, ProgressBar, Text } from '@razorpay/blade/components';
import { ClickAnalytics, OnboardingStep } from './MerchantOnboardingConfig';
import useOnboardingContext from './providers/useOnboardingContext';
import OnboardingStepCard from 'apps/pos/src/app/components/OnboardingStepCard/OnboardingStepCard';
import OnboardingHeader from 'apps/pos/src/app/components/OnboardingHeader';
import { trackEvent } from 'apps/pos/src/services/analytics';
import { PricingNcStatus } from 'apps/pos/src/app/types/PaymentsAndService';
import { AllBadgeTypes } from 'apps/pos/src/app/types/common';

interface HandleStepCardClickProps {
  step: OnboardingStep;
  analytics: ClickAnalytics | undefined;
}

const MerchantOnboardingLanding = (): JSX.Element => {
  const { values, states, handlers } = useOnboardingContext();
  const { onboardingSteps } = values;
  const { isModularLoading } = states;
  const { handleStepClick, getOnboardingProgress } = handlers;
  const { totalSteps, totalCompletedSteps } = getOnboardingProgress();

  const handleOnStepCardClick = ({ step, analytics }: HandleStepCardClickProps): void => {
    handleStepClick({ step });
    if (analytics) {
      trackEvent(analytics);
    }
  };

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
        const { slug, title, description, icon, getStatus, checkIfDisabled, clickAnalytics } = step;
        return (
          <OnboardingStepCard
            pricingNcStatus={
              (states.merchantDetails?.activation.posPricingNcStatus?.toLocaleLowerCase() as PricingNcStatus) ??
              null
            }
            key={slug}
            slug={slug}
            title={`${index + 1}. ${title}`}
            description={description}
            icon={icon}
            status={getStatus({ values, states }) as AllBadgeTypes}
            isDisabled={checkIfDisabled({ values, states })}
            onClick={() => handleOnStepCardClick({ step, analytics: clickAnalytics })}
          />
        );
      })}
    </Box>
  );
};

export { MerchantOnboardingLanding };
