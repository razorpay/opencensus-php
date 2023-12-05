// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
import '@testing-library/jest-dom/extend-expect';
import * as Messages from 'merchant/views/onboarding/mobile/ActivationModals/Constant';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import OnboardingCard from 'merchant/views/onboarding/mobile/OnboardingCard/index';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as DataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import React from 'react';
import { render, screen, waitFor } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

jest.mock('merchant/views/onboarding/mobile/hooks/useActivation');

test('should show payment disable modal', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.OnboardingMileStoneL1,
      poi_verification_status: 'failed',
    }),
  });
  render(<OnboardingCard referee={undefined} />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.PAYMENT_DISABLE.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.PAYMENT_DISABLE.description)).toBeInTheDocument();
    expect(screen.getByText(Messages.PAYMENT_DISABLE.buttonText)).toBeInTheDocument();
  });
});

test('should show activation progress %', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      activation_progress: 45,
    }),
  });
  render(<OnboardingCard referee={undefined} />, {});

  await waitFor(() => {
    expect(screen.getByText('Account Activation')).toBeInTheDocument();
    expect(screen.getByText('45% complete')).toBeInTheDocument();
  });
});
