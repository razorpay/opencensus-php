// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as Messages from 'merchant/views/onboarding/mobile/OnboardingCard/Constants';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as DataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import OnboardingCardShimmer from 'merchant/views/onboarding/mobile/OnboardingCard/OnboardingCardShimmer';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import useEscalation from 'merchant/views/onboarding/mobile/hooks/useEscalation';
import CurrentActivationProgress from 'merchant/views/onboarding/mobile/OnboardingCard/CurrentActivationProgress';
import {
  fireEvent,
  render,
  screen,
  waitForElementToBeRemoved,
  waitFor,
  server,
  cleanup,
} from 'test-utils';
import { fetchEligibilityHandler } from 'merchant/views/onboarding/mobile/OnboardingCard/handlers';

jest.mock('merchant/views/onboarding/mobile/hooks/useActivation');

beforeEach(() => {
  cleanup();
  ActivationDB.reset();
});

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('shimmer')], { timeout: 4000 });

const App: React.FC = () => {
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { status: escalationsStatus, data: escalationsData } = useEscalation();
  if (activationQueryStatus === 'loading' || escalationsStatus === 'loading')
    return <OnboardingCardShimmer />;
  return (
    <CurrentActivationProgress
      referee={undefined}
      data={activationData}
      escalation={escalationsData}
    />
  );
};

test('should render null incase of activation progress is 90 and activation status is activation_mcc_pending', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.OnboardingMileStoneL2,
      ...DataPieces.activationStatus.mccPending,
      ...DataPieces.activationProgress.activationMccPending,
    }),
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(screen.queryByText('% complete')).not.toBeInTheDocument();
  });
});

test('should render correct message for poi_verification_status = incorrect_details', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.POIStatus.incorrect_details,
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(
      screen.getByText(Messages.POI_VERIFICATION_STATUS.incorrect_details.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(Messages.POI_VERIFICATION_STATUS.incorrect_details.description),
    ).toBeInTheDocument();
  });
});

test('should render correct message for poi_verification_status = failed', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.POIStatus.failed,
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.POI_VERIFICATION_STATUS.failed.title)).toBeInTheDocument();
    expect(
      screen.getByText(Messages.POI_VERIFICATION_STATUS.failed.description),
    ).toBeInTheDocument();
  });
});

test('should render correct message for poi_verification_status = pending', () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.POIStatus.pending,
    }),
  });
  render(<App />, {});
  expect(() => screen.getByText(Messages.POI_VERIFICATION_STATUS.pending.title)).toThrow();
  expect(() => screen.getByText(Messages.POI_VERIFICATION_STATUS.pending.description)).toThrow();
});

test('should render correct message for bank_details_verification_status = failed', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.OnboardingMileStoneL2,
      ...DataPieces.POIStatus.pending,
      bank_details_verification_status: 'failed',
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(
      screen.getByText(Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.description),
    ).toBeInTheDocument();
  });
});

test('should render correct message for activation_status = under_review if payment is enabled', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      ...DataPieces.POIStatus.pending,
      ...DataPieces.PaymentEnable,
      activation_status: 'under_review',
    }),
  });
  render(<App />, {});

  await waitFor(() => {
    expect(
      screen.getByText(Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.description_with_payment_enable,
      ),
    ).toBeInTheDocument();
  });
});

test('should render correct message for activation_status = needs_clarification', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      ...DataPieces.POIStatus.pending,
      activation_status: 'needs_clarification',
    }),
  });
  render(<App />, {});

  await waitFor(() => {
    fireEvent.click(screen.getByText('Clarify Details'));
    expect(
      screen.getByText(Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description.normal),
    ).toBeInTheDocument();
  });
});

test('should render correct message for activation_status = activated', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowGG,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      ...DataPieces.POIStatus.pending,
      activation_status: 'activated',
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    fireEvent.click(screen.getByText('Switch To Live Mode'));
    expect(screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED.description)).toBeInTheDocument();
  });
});

test('should render correct message of dedupe for L1', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowUnreg,
      ...DataPieces.contactDetails,
      ...DataPieces.unregBusinessOverview,
      ...DataPieces.businessDetails,
      ...DataPieces.OnboardingMileStoneL1,
      dedupe: {
        isUnderReview: false,
        isMatch: true,
      },
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.DEDUPE.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.DEDUPE.description)).toBeInTheDocument();
  });
});

test('should render correct message of dedupe for L2', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowUnreg,
      ...DataPieces.regBusinessOverview,
      activation_form_milestone: 'L2',
      dedupe: {
        isUnderReview: false,
        isMatch: true,
      },
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    fireEvent.click(screen.getByText('Contact Support'));
    expect(screen.getByText(Messages.DEDUPE.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.DEDUPE.description)).toBeInTheDocument();
  });
});

test('should render correct message if L1 is not submitted', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    fireEvent.click(screen.getByText('Submit KYC'));
    expect(screen.getByText(Messages.ACTIVATION_PROGRESS.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.ACTIVATION_PROGRESS.description)).toBeInTheDocument();
  });
});

test('should render correct message if activation_status = rejected', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'rejected',
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.ACTIVATION_STATUS_REJECTED.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.ACTIVATION_STATUS_REJECTED.description)).toBeInTheDocument();
  });
});

test('should render correct message if flow is greylist', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL1,
      dedupe: {
        isUnderReview: true,
        isMatch: true,
      },
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.GREYLIST_STEP.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.GREYLIST_STEP.description)).toBeInTheDocument();
  });
});

test('should render correct message for under review tnc flow', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'under_review',
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.GENERATE_TNC.under_review.old_title)).toBeInTheDocument();
    expect(screen.getByText(Messages.GENERATE_TNC.under_review.description)).toBeInTheDocument();
    expect(screen.getByText('View submitted details')).toBeInTheDocument();
    expect(screen.getByText('Generate Terms And Conditions')).toBeInTheDocument();
  });
});

test('should render correct message for activated mcc pendingw tnc flow', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'activated_mcc_pending',
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText('Generate Terms And Conditions')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Generate Terms And Conditions'));
    expect(screen.getByText(Messages.GENERATE_TNC.mcc_pending.description)).toBeInTheDocument();
    expect(screen.getByText(Messages.GENERATE_TNC.mcc_pending.title)).toBeInTheDocument();
  });
});

test('should render correct message when merchant reached hard limit', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.OnboardingMileStoneL2,
      isHardLimitReached: true,
      merchant: {
        hold_funds: true,
      },
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.HARD_LIMIT_REACHED.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.HARD_LIMIT_REACHED.description)).toBeInTheDocument();
    expect(screen.getByText('More details')).toBeInTheDocument();
  });
});

test('should render correct message for activated_mcc_pending', async () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'activated_mcc_pending',
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(
      screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.new_title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.new_description),
    ).toBeInTheDocument();
  });
});

//TODO: Check if this test is required
test('should not render any message', () => {
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      activation_status: 'activated_mcc_pending',
    }),
  });
  render(<App />, {});
  expect(screen.queryByText('null')).not.toBeInTheDocument();
});

test('should show action required in case of needs clarification message which includes with payments and settlement enabled', async () => {
  await server.use(fetchEligibilityHandler());
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      isEasyNcEnabled: true,
      activation_status: 'needs_clarification',
      ...DataPieces.needsClarificationPaymentsSettlementEnabled,
    }),
  });
  window.sessionStorage.setItem('isNewNc', 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmiERi');
  window.session_id = 'oCrgOzCvkfG6Qh5f4xgk5hfOb3CL4JAhCuwmtrRi';

  render(<App />, {});
  await waitFor(() => {
    expect(screen.queryByText('ACTION REQUIRED')).toBeInTheDocument();
  });
});

test('should show needs clarification messafe with payments enabled', async () => {
  await server.use(fetchEligibilityHandler());
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      isEasyNcEnabled: true,
      activation_status: 'needs_clarification',
      ...DataPieces.needsClarificationWithPaymentsEnabled,
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(
      screen.queryByText(
        'You’ll be able to receive collected payments in your account only after the required details are updated',
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText('ACTION REQUIRED')).toBeInTheDocument();
  });
});

test('should show needs clarification messafe with payments disabled', async () => {
  await server.use(fetchEligibilityHandler());
  useActivation.mockReturnValue({
    status: 'success',
    data: ActivationDB.update({
      ...DataPieces.ActivationFlowWW,
      ...DataPieces.regBusinessOverview,
      ...DataPieces.OnboardingMileStoneL2,
      isEasyNcEnabled: true,
      activation_status: 'needs_clarification',
      ...DataPieces.needsClarificationWithPaymentDisabled,
    }),
  });
  render(<App />, {});
  await waitFor(() => {
    expect(
      screen.queryByText(
        'Update these details to help us activate your account faster once we resume onboarding new businesses',
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText('ACTION REQUIRED')).toBeInTheDocument();
  });
});
