import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ActivationProgress from '../index';
import * as ActivationDB from '../../../services/data/ActivationDB';
import * as ActivationDataPieces from '../../../services/data/pieces';
import useActivation from '../../../hooks/useActivation';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <ActivationProgress />;
};

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => screen.queryByText('Loading...'));

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
    screen.getByText(
      'We can’t support your business because it doesn’t meet our compliance requirements',
    ),
  ).toBeInTheDocument();
  expect(() => screen.getByText('Submit KYC')).toThrow();
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
});
