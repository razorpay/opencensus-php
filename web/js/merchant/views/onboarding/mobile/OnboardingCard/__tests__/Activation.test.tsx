import '@testing-library/jest-dom/extend-expect';
import * as Messages from 'merchant/views/onboarding/mobile/ActivationModals/Constant';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import OnboardingCard from 'merchant/views/onboarding/mobile/OnboardingCard/index';
import OnboardingCardShimmer from 'merchant/views/onboarding/mobile/OnboardingCard/OnboardingCardShimmer';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as DataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import React from 'react';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

const waitForOnboardingPageLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('shimmer')], { timeout: 4000 });

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <OnboardingCardShimmer />;
  return <OnboardingCard referee={undefined} />;
};

test('should show payment disable modal', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneL1,
    poi_verification_status: 'failed',
  });
  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.buttonText)).toBeInTheDocument();
});

test('should show activation progress %', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.regBusinessOverview,
    activation_progress: 45,
  });
  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText('Account Activation')).toBeInTheDocument();
  expect(screen.getByText('45% complete')).toBeInTheDocument();
});
