import '@testing-library/jest-dom/extend-expect';
import * as Messages from 'merchant/views/onboarding/mobile/ActivationModals/Constant';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import { fetchEligibilityHandler } from 'merchant/views/onboarding/mobile/OnboardingCard/handlers';
import OnboardingCard from 'merchant/views/onboarding/mobile/OnboardingCard/index';
import OnboardingCardShimmer from 'merchant/views/onboarding/mobile/OnboardingCard/OnboardingCardShimmer';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as DataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import React from 'react';
import { render, screen, server, waitFor, waitForElementToBeRemoved } from 'test-utils';

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
});

test('should show NeedsClarification modal with payments and settlement enabled  ', async () => {
  await server.use(fetchEligibilityHandler());
  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    activation_status: 'needs_clarification',
    ...DataPieces.needsClarificationPaymentsSettlementEnabled,
  });

  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();

  await waitFor(() => {
    const title = screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title)[0];
    expect(title).toBeInTheDocument();
    expect(
      screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText)[0],
    ).toBeInTheDocument();
  });
});

test('should show NeedsClarification modal with payment only enabled', async () => {
  await server.use(fetchEligibilityHandler());
  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    activation_status: 'needs_clarification',
    ...DataPieces.needsClarificationWithPaymentsEnabled,
  });

  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';

  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  await waitFor(() => {
    const title = screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title)[0];
    expect(title).toBeInTheDocument();
    expect(
      screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText)[0],
    ).toBeInTheDocument();
  });
});

test('should show NeedsClarification modal with payment disabled ', async () => {
  await server.use(fetchEligibilityHandler());
  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    activation_status: 'needs_clarification',
    ...DataPieces.needsClarificationWithPaymentDisabled,
  });

  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';

  render(<App />, {});
  await waitForOnboardingPageLoadingToFinish();
  await waitFor(() => {
    const title = screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title)[0];
    expect(title).toBeInTheDocument();
    expect(
      screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText)[0],
    ).toBeInTheDocument();
  });
});
