// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ActivationProgress from 'merchant/views/onboarding/mobile/Screens/ActivationProgress/index';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as ActivationDataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import { render, screen, fireEvent, waitFor } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

jest.mock('merchant/views/onboarding/mobile/hooks/useActivation');

test('should render whitelist flow', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...ActivationDataPieces.ActivationFlowWG,
      ...ActivationDataPieces.regBusinessOverview,
    }),
  });
  render(<ActivationProgress />);
  await waitFor(() => {
    expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Submit these details to accept payments and receive settlements in your account',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Contact Details')).toBeInTheDocument();
    expect(screen.getByText('Business Overview')).toBeInTheDocument();
    expect(screen.getByText('Business Details')).toBeInTheDocument();
    expect(() => screen.getByText('Bank and Business Details')).toThrow();
    expect(() => screen.getByText('Documents Upload')).toThrow();
    fireEvent.click(screen.getByText('Contact Details'));
    fireEvent.click(screen.getByText('Submit KYC'));
  });
});

test('should show error info if dedupe blocked and button should not be present', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...ActivationDataPieces.ActivationFlowWG,
      ...ActivationDataPieces.regBusinessOverview,
      dedupe: {
        isUnderReview: false,
        isMatch: true,
      },
    }),
  });
  render(<ActivationProgress />);
  await waitFor(() => {
    expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
    expect(
      screen.queryByText(
        'We can’t support your business because it doesn’t meet our compliance requirements',
      ),
    ).toBeNull();
  });
});

test('should render greylist flow and milestone = L1', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...ActivationDataPieces.ActivationFlowWG,
      ...ActivationDataPieces.regBusinessOverview,
      ...ActivationDataPieces.OnboardingMileStoneL1,
      ...ActivationDataPieces.PaymentEnable,
    }),
  });
  render(<ActivationProgress />);

  await waitFor(() => {
    expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Submit all the details and get your KYC approved to complete account activation and enable settlements',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Contact Details')).toBeInTheDocument();
    expect(screen.getByText('Business Overview')).toBeInTheDocument();
    expect(screen.getByText('Business Details')).toBeInTheDocument();
    expect(screen.getByText('Bank and Business Details')).toBeInTheDocument();
    expect(screen.getByText('Documents Upload')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Submit KYC'));
    expect(screen.queryByText('What Are Settlements')).toBeInTheDocument();
  });
});

test('should render nc flow', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...ActivationDataPieces.ActivationFlowWG,
      ...ActivationDataPieces.regBusinessOverview,
      ...ActivationDataPieces.OnboardingMileStoneL2,
      activation_form_milestone: 'L2',
      activation_status: 'needs_clarification',
    }),
  });
  render(<ActivationProgress />);
  await waitFor(() => {
    expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Please provide clarification regarding some issues with your submitted details by on your web dashboard',
      ),
    ).toBeInTheDocument();
    fireEvent.click(screen.getByText('Clarify Details'));
    expect(screen.queryByText('What Are Settlements')).toBeInTheDocument();
  });
});

test('should render under review flow', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...ActivationDataPieces.ActivationFlowWG,
      ...ActivationDataPieces.regBusinessOverview,
      ...ActivationDataPieces.OnboardingMileStoneL2,
      activation_form_milestone: 'L2',
      activation_status: 'under_review',
    }),
  });
  render(<ActivationProgress />);

  await waitFor(() => {
    expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
    expect(
      screen.getByText('You have submitted all the details. Our team is reviewing them'),
    ).toBeInTheDocument();
    fireEvent.click(screen.getByText('Contact Details'));
    expect(screen.queryByText('What Are Settlements')).toBeInTheDocument();
  });
});

test('should render activated_mcc_pending flow', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...ActivationDataPieces.ActivationFlowWG,
      ...ActivationDataPieces.regBusinessOverview,
      ...ActivationDataPieces.OnboardingMileStoneL2,
      activation_form_milestone: 'L2',
      activation_status: 'activated_mcc_pending',
    }),
  });
  render(<ActivationProgress />);

  await waitFor(() => {
    expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Payments and settlements have been enabled. We might do some periodic checks for your KYC and ask for clarifications',
      ),
    ).toBeInTheDocument();
  });
});
