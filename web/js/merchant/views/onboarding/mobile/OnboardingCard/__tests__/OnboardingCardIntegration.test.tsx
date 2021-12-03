import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as DataPieces from '../../services/data/pieces';
import * as Messages from '../../ActivationModals/Constant';
import OnboardingCard from '../index';
import useActivation from '../../hooks/useActivation';
import OnboardingCardShimmer from '../OnboardingCardShimmer';
import { render, waitForElementToBeRemoved, screen, fireEvent } from 'test-utils';

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
  fireEvent.click(screen.getByText(Messages.PAYMENT_DISABLE.buttonText));
});

test('should show payment enable modal', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneL1,
    ...DataPieces.PaymentEnable,
    poi_verification_status: 'verified',
  });
  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.buttonText)).toBeInTheDocument();
});

test('should show NC modal', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    activation_status: 'needs_clarification',
  });
  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  expect(screen.getByText(Messages.NC.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.NC.description.normal_nc)).toBeInTheDocument();
  expect(screen.getByText(Messages.NC.buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.NC.buttonText));
});
