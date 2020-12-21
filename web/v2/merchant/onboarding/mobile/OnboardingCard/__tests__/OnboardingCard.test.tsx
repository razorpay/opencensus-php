import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import OnboardingCard from '../index';
import * as Messages from '../Constants';
import * as PaymentsDB from '../../services/data/PaymentsDB';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as DataPieces from '../../services/data/pieces';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
  PaymentsDB.reset();
});

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('loader')], { timeout: 4000 });

test('should render correct message for poi_verification_status = incorrect_details', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.OnboardingMileStoneBizPicker,
    ...DataPieces.POIStatus.incorrect_details,
  });
  render(<OnboardingCard />, {});
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
    ...DataPieces.OnboardingMileStoneBizPicker,
    ...DataPieces.POIStatus.failed,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.POI_VERIFICATION_STATUS.failed.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.POI_VERIFICATION_STATUS.failed.description)).toBeInTheDocument();
});

test('should render correct message for poi_verification_status = pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.OnboardingMileStoneBizPicker,
    ...DataPieces.POIStatus.pending,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.POI_VERIFICATION_STATUS.pending.title)).toBeInTheDocument();
  expect(
    screen.getByText(Messages.POI_VERIFICATION_STATUS.pending.description),
  ).toBeInTheDocument();
});

test('should render correct message for bank_details_verification_status = failed', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.OnboardingMileStoneL2,
    ...DataPieces.POIStatus.pending,
    bank_details_verification_status: 'failed',
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.description),
  ).toBeInTheDocument();
});

test('should render correct message for activation_status = under_review', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL2,
    ...DataPieces.POIStatus.pending,
    activation_status: 'under_review',
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_UNDER_REVIEW.registered.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_UNDER_REVIEW.registered.description),
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
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description),
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
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.ACTIVATION_STATUS_ACTIVATED.description)).toBeInTheDocument();
});

test('should render correct message for AF = greylist and IAF = greylist when multiple steps are pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_gl.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_gl.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = greylist and IAF = greylist when one step is pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGG,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_gl.single.title)).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_gl.single.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = greylist and IAF = blacklist when multiple steps are pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGB,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_bl.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_bl.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = greylist and IAF = blacklist when one step is pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowGB,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_bl.single.title)).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_gl.iaf_bl.single.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = whitelist when multiple payment enable steps pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_payments.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_payments.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = whitelist when only one payment enable step pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.OnboardingMileStoneBizPicker,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_payments.single.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_payments.single.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = whitelist when multiple enable-settlement steps pending (live transaction done)', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.multiple.title,
    ),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.multiple
        .description,
    ),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = whitelist when only one enable-settlement step pending (live transaction done)', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.single.title,
    ),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.single
        .description,
    ),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = whitelist when multiple enable-settlement steps pending (live transaction not done)', async () => {
  PaymentsDB.update({ items: [] });
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_not_done.multiple
        .title,
    ),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_not_done.multiple
        .description,
    ),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = whitelist when only one enable-settlement step pending (live transaction not done)', async () => {
  PaymentsDB.update({ items: [] });
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_not_done.single
        .title,
    ),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_not_done.single
        .description,
    ),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = greylist when multiple payment enable steps pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWG,
    ...DataPieces.OnboardingMileStoneBizPicker,
    business_type: 1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_payments.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_payments.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = greylist when only one payment enable step pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWG,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_payments.single.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_payments.single.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = greylist when multiple enable-settlement steps pending', async () => {
  PaymentsDB.update({ items: [] });
  ActivationDB.update({
    ...DataPieces.ActivationFlowWG,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_settlements.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_settlements.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for AF = whitelist and IAF = greylist when only one enable-settlement step pending', async () => {
  PaymentsDB.update({ items: [] });
  ActivationDB.update({
    ...DataPieces.ActivationFlowWG,
    ...DataPieces.contactDetails,
    ...DataPieces.regBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_settlements.single.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_settlements.single.description),
  ).toBeInTheDocument();
});

test('should render correct message for unregistered when multiple payment enable steps pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowUnreg,
    ...DataPieces.OnboardingMileStoneBizPicker,
    business_type: '11',
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_payments.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_payments.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for unregistered when only one payment enable step pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowUnreg,
    ...DataPieces.contactDetails,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.OnboardingMileStoneBizPicker,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_payments.single.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_payments.single.description),
  ).toBeInTheDocument();
});

test('should render correct message for unregistered when multiple settlement enable step pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowUnreg,
    ...DataPieces.contactDetails,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_settlements.multiple.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_settlements.multiple.description),
  ).toBeInTheDocument();
});

test('should render correct message for unregistered when only one settlement enable step pending', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowUnreg,
    ...DataPieces.contactDetails,
    ...DataPieces.unregBusinessOverview,
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<OnboardingCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_settlements.single.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.REMAINING_STEPS.unregistered.enable_settlements.single.description),
  ).toBeInTheDocument();
});
