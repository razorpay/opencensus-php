import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import AcceptPaymentsCard from '../index';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as InternationalWorkflowDB from '../../services/data/InternationalWorkflowDB';
import * as WebsiteWorkflowDB from '../../services/data/WebsiteWorkflowDB';
import * as ActivationDataPieces from '../../services/data/pieces';
import * as Messages from '../Constants';
import { NOT_REGISTERED, PROPRIETORSHIP } from '../../Constants/OnboardingConstants';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('payment_card')], { timeout: 8000 });

afterEach(() => {
  ActivationDB.reset();
  InternationalWorkflowDB.reset();
  WebsiteWorkflowDB.reset();
});

test.skip('should render correct message for unregistered merchant after submitting L1', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowUnreg,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    business_type: NOT_REGISTERED,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.unreg.l1_submitted.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.unreg.l1_submitted.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF whitelist IAF greylist merchant after submitting L1', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.l1_submitted.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.l1_submitted.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF whitelist IAF greylist merchant after comepleting activation', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.account_activated.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.account_activated.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF whitelist IAF whitelist merchant after submitting L1 (no website)', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF whitelist IAF whitelist merchant after submitting L1 (has website)', async () => {
  InternationalWorkflowDB.update({
    payment_gateway: 'approved',
  });
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    business_website: 'https://www.google.com',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.has_website.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.has_website.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF whitelist IAF whitelist merchant after submitting L2 (no website)', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.no_website.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.no_website.description,
    ),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF whitelist IAF whitelist merchant after submitting L2 (has website)', async () => {
  InternationalWorkflowDB.update({
    payment_gateway: 'approved',
  });
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    business_website: 'https://www.google.com',
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.title),
  ).toBeInTheDocument();
  expect(
    screen.getByText(
      Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.description,
    ),
  ).toBeInTheDocument();
});

test.skip('should render correct message for AF greylist IAF blacklist merchant after submitting L2', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowGB,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    business_type: PROPRIETORSHIP,
    activation_status: 'activated',
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.INTERNATIONAL_BLACKLIST.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.INTERNATIONAL_BLACKLIST.description)).toBeInTheDocument();
});

test.skip('should render correct message for AF greylist IAF greylist merchant after submitting L1', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowGG,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.title)).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message if international payments request is in review', async () => {
  InternationalWorkflowDB.update({
    payment_gateway: 'in_review',
  });
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowGG,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.INTERNATIONAL_REQUEST.in_review.title)).toBeInTheDocument();
  expect(
    screen.getByText(Messages.INTERNATIONAL_REQUEST.in_review.description),
  ).toBeInTheDocument();
});

test.skip('should render correct message if international payments request was rejected', async () => {
  InternationalWorkflowDB.update({
    payment_gateway: 'rejected',
  });
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowGG,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<AcceptPaymentsCard />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(Messages.INTERNATIONAL_REQUEST.rejected.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.INTERNATIONAL_REQUEST.rejected.description)).toBeInTheDocument();
});
