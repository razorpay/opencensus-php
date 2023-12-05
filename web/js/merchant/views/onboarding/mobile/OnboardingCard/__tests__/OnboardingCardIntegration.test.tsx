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

//Mock the required hooks used within the react component.
jest.mock('merchant/views/onboarding/mobile/hooks/useActivation');

describe('<OnboardingCard /> ', () => {
  // We can add one more test case to test the loading state behaviour of OnboardingCard.
  test('should show payment enable modal', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...DataPieces.ActivationFlowWW,
        ...DataPieces.unregBusinessOverview,
        ...DataPieces.OnboardingMileStoneL1,
        ...DataPieces.PaymentEnable,
        poi_verification_status: 'verified',
      }),
    });
    render(<OnboardingCard />, {});
    await waitFor(() => {
      expect(screen.getByText(Messages.PAYMENT_ENABLE.title)).toBeInTheDocument();
      expect(screen.getByText(Messages.PAYMENT_ENABLE.description)).toBeInTheDocument();
      expect(screen.getByText(Messages.PAYMENT_ENABLE.buttonText)).toBeInTheDocument();
    });
  });

  test('should show paused message is case of block', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...DataPieces.ActivationFlowWW,
        ...DataPieces.regBusinessOverview,
        dedupe: {
          isUnderReview: false,
          isMatch: true,
        },
      }),
    });
    render(<OnboardingCard />, {});
    await waitFor(() => {
      expect(screen.getByText('Account Activation')).toBeInTheDocument();
      expect(screen.queryByText('Paused')).toBeNull();
    });
  });
});
