import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as DataPieces from '../../services/data/pieces';
import OnboardingCard from '../index';
import useActivation from '../../hooks/useActivation';
import OnboardingCardShimmer from '../OnboardingCardShimmer';
import { render, waitForElementToBeRemoved, screen } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

const waitForOnboardingPageLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('shimmer')], { timeout: 4000 });

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <OnboardingCardShimmer />;
  return <OnboardingCard />;
};
test('should show paused message is case of block', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.regBusinessOverview,
    dedupe: {
      isUnderReview: false,
      isMatch: true,
    },
  });
  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText('Account Activation')).toBeInTheDocument();
  expect(screen.getByText('Paused')).toBeInTheDocument();
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
