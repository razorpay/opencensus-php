import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as Messages from '../Constants';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as DataPieces from '../../services/data/pieces';
import OnboardingCardShimmer from '../OnboardingCardShimmer';
import useActivation from '../../hooks/useActivation';
import useEscalation from '../../hooks/useEscalation';
import CurrentActivationProgress from 'v2/merchant/onboarding/mobile/OnboardingCard/CurrentActivationProgress';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('shimmer')], { timeout: 4000 });

const App: React.FC = () => {
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { status: escalationsStatus, data: escalationsData } = useEscalation();
  if (activationQueryStatus === 'loading' || escalationsStatus === 'loading')
    return <OnboardingCardShimmer />;
  return <CurrentActivationProgress data={activationData} escalation={escalationsData} />;
};

test('should render correct message for poi_verification_status = incorrect_details', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.POIStatus.incorrect_details,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.POI_VERIFICATION_STATUS.incorrect_details.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.POI_VERIFICATION_STATUS.incorrect_details.description),
  ).toBeInTheDocument();
});

test('should render correct message for poi_verification_status = failed', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.POIStatus.failed,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.POI_VERIFICATION_STATUS.failed.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.POI_VERIFICATION_STATUS.failed.description)).toBeInTheDocument();
});

test('should render correct message for poi_verification_status = pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.POIStatus.pending,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(() => screen.getByText(Messages.POI_VERIFICATION_STATUS.pending.title)).toThrow();
  expect(() => screen.getByText(Messages.POI_VERIFICATION_STATUS.pending.description)).toThrow();
});

test('should render correct message for bank_details_verification_status = failed', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.OnboardingMileStoneL2,
    ...DataPieces.POIStatus.pending,
    bank_details_verification_status: 'failed',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.description),
  ).toBeInTheDocument();
});

test('should render correct message for activation_status = under_review if payment is enabled', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    ...DataPieces.POIStatus.pending,
    ...DataPieces.PaymentEnable,
    activation_status: 'under_review',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.description_with_payment_enable,
    ),
  ).toBeInTheDocument();
});

test('should render correct message for activation_status = needs_clarification', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    ...DataPieces.POIStatus.pending,
    activation_status: 'needs_clarification',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description.normal),
  ).toBeInTheDocument();
});

test('should render correct message for activation_status = activated', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    ...DataPieces.POIStatus.pending,
    activation_status: 'activated',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED.description)).toBeInTheDocument();
});

test('should render correct message of dedupe for L1', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowUnreg,
    ...DataPieces.contactDetails,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.OnboardingMileStoneL1,
    dedupe: {
      isUnderReview: false,
      isMatch: true,
    },
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.DEDUPE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.DEDUPE.description)).toBeInTheDocument();
});

test('should render correct message of dedupe for L2', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowUnreg,
    ...DataPieces.regBusinessOverview,
    activation_form_milestone: 'L2',
    dedupe: {
      isUnderReview: false,
      isMatch: true,
    },
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.DEDUPE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.DEDUPE.description)).toBeInTheDocument();
});

test('should render correct message if L1 is not submitted', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.regBusinessOverview,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.ACTIVATION_PROGRESS.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.ACTIVATION_PROGRESS.description)).toBeInTheDocument();
});

test('should render correct message if activation_status = rejected', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    activation_status: 'rejected',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.ACTIVATION_STATUS_REJECTED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.ACTIVATION_STATUS_REJECTED.description)).toBeInTheDocument();
});

test('should render correct message if flow is greylist', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL1,
    dedupe: {
      isUnderReview: true,
      isMatch: true,
    },
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.GREYLIST_STEP.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.GREYLIST_STEP.description)).toBeInTheDocument();
});
