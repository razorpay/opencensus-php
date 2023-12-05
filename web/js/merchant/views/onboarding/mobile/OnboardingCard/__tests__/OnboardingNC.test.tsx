// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
import '@testing-library/jest-dom/extend-expect';
import * as Messages from 'merchant/views/onboarding/mobile/ActivationModals/Constant';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import { fetchEligibilityHandler } from 'merchant/views/onboarding/mobile/OnboardingCard/handlers';
import OnboardingCard from 'merchant/views/onboarding/mobile/OnboardingCard/index';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as DataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import React from 'react';
import { render, screen, server, waitFor } from 'test-utils';

jest.mock('merchant/views/onboarding/mobile/hooks/useActivation');

afterEach(() => {
  ActivationDB.reset();
});

test('should show NC modal', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'needs_clarification',
    }),
  });
  render(<OnboardingCard referee={undefined} />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.NC.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.NC.description.normal_nc)).toBeInTheDocument();
    expect(screen.getByText(Messages.NC.buttonText)).toBeInTheDocument();
  });
});

test('should show NeedsClarification modal with payments and settlement enabled  ', async () => {
  await server.use(fetchEligibilityHandler());
  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'needs_clarification',
      ...DataPieces.needsClarificationPaymentsSettlementEnabled,
    }),
  });

  render(<OnboardingCard referee={undefined} />, {});

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
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'needs_clarification',
      ...DataPieces.needsClarificationWithPaymentsEnabled,
    }),
  });

  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';

  render(<OnboardingCard referee={undefined} />, {});
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
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'needs_clarification',
      ...DataPieces.needsClarificationWithPaymentDisabled,
    }),
  });

  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';

  render(<OnboardingCard referee={undefined} />, {});
  await waitFor(() => {
    const title = screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title)[0];
    expect(title).toBeInTheDocument();
    expect(
      screen.getAllByText(Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText)[0],
    ).toBeInTheDocument();
  });
});
