import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ActivationProgress from '../index';
import * as ActivationDB from '../../../services/data/ActivationDB';
import * as ActivationDataPieces from '../../../services/data/pieces';
import useActivation from '../../../hooks/useActivation';
import { render, screen, waitForElementToBeRemoved, fireEvent } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => screen.queryByText('Loading...'));

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <ActivationProgress />;
};

test('should render whitelist flow', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.regBusinessOverview,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
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

test('should show error info if dedupe blocked and button should not be present', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.regBusinessOverview,
    dedupe: {
      isUnderReview: false,
      isMatch: true,
    },
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
  expect(
    screen.queryByText(
      'We can’t support your business because it doesn’t meet our compliance requirements',
    ),
  ).toBeNull();
});

test('should render greylist flow and milestone = L1', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.regBusinessOverview,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    ...ActivationDataPieces.PaymentEnable,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
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
});

test('should render nc flow', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.regBusinessOverview,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_form_milestone: 'L2',
    activation_status: 'needs_clarification',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
  expect(
    screen.getByText(
      'Please provide clarification regarding some issues with your submitted details by on your web dashboard',
    ),
  ).toBeInTheDocument();
  fireEvent.click(screen.getByText('Clarify Details'));
});

test('should render under review flow', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.regBusinessOverview,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_form_milestone: 'L2',
    activation_status: 'under_review',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
  expect(
    screen.getByText('You have submitted all the details. Our team is reviewing them'),
  ).toBeInTheDocument();
  fireEvent.click(screen.getByText('Contact Details'));
});

test('should render activated_mcc_pending flow', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.regBusinessOverview,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_form_milestone: 'L2',
    activation_status: 'activated_mcc_pending',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Submit KYC details')).toBeInTheDocument();
  expect(
    screen.getByText(
      'Payments and settlements have been enabled. We might do some periodic checks for your KYC and ask for clarifications',
    ),
  ).toBeInTheDocument();
});
