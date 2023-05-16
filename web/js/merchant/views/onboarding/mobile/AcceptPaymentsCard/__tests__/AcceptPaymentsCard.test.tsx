import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { useQuery } from 'react-query';
import AcceptPaymentsCard from 'merchant/views/onboarding/mobile/AcceptPaymentsCard/index';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import * as InternationalWorkflowDB from 'merchant/views/onboarding/mobile/services/data/InternationalWorkflowDB';
import * as WebsiteWorkflowDB from 'merchant/views/onboarding/mobile/services/data/WebsiteWorkflowDB';
import * as PaymentEscalationDB from 'merchant/views/onboarding/mobile/services/data/PaymentEscalationDB';
import * as ActivationDataPieces from 'merchant/views/onboarding/mobile/services/data/pieces';
import * as Messages from 'merchant/views/onboarding/mobile/AcceptPaymentsCard/Constants';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import useEscalation from 'merchant/views/onboarding/mobile/hooks/useEscalation';
import { PROPRIETORSHIP } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { render, screen, waitFor, waitForElementToBeRemoved } from 'test-utils';
import { fetch } from 'common/services/rest/rest-fetch';

beforeEach(() => {
  ActivationDB.reset();
  InternationalWorkflowDB.reset();
  WebsiteWorkflowDB.reset();
  PaymentEscalationDB.reset();
});

const fetchInternationalProductStatus = () =>
  fetch<any>({ url: 'merchants/product_international/workflow/status/all', mode: 'live' }).then(
    (res: any) => {
      return res.data;
    },
  );

const App: React.FC = () => {
  const { status } = useActivation();
  const { status: workFlowStatus } = useQuery(
    'internationalWorkflowStatus',
    fetchInternationalProductStatus,
    {
      retry: false,
      staleTime: Infinity,
    },
  );
  const { status: escalationsStatus } = useEscalation();

  if (status === 'loading' || workFlowStatus === 'loading' || escalationsStatus === 'loading')
    return <div>Loading...</div>;
  return <AcceptPaymentsCard />;
};

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => screen.queryByText('Loading...'));

test('should render correct message for AF whitelist IAF greylist merchant after comepleting activation', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWG,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<App />, {});
  await waitForLoadingToFinish();

  await waitFor(() => {
    expect(
      screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.account_activated.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.account_activated.description),
    ).toBeInTheDocument();
  });
});

test('should render correct message for AF whitelist IAF whitelist merchant after submitting L1 (no website)', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    business_type: PROPRIETORSHIP,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(
      screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.description,
      ),
    ).toBeInTheDocument();
  });
});

test('should render correct message for AF whitelist IAF whitelist merchant after submitting L1 (has website)', async () => {
  InternationalWorkflowDB.update({
    payment_gateway: 'approved',
  });
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL1,
    business_website: 'https://www.google.com',
    business_type: PROPRIETORSHIP,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(
      screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.has_website.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.has_website.description,
      ),
    ).toBeInTheDocument();
  });
});

test('should render correct message for AF whitelist IAF whitelist merchant after submitting L2 (no website)', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    activation_status: 'activated',
    business_type: PROPRIETORSHIP,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(
      screen.getByText(Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.no_website.title),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.no_website.description,
      ),
    ).toBeInTheDocument();
  });
});

test('should render correct message for AF whitelist IAF whitelist merchant after submitting L2 (has website)', async () => {
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
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(
      screen.getByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.title,
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.description,
      ),
    ).toBeInTheDocument();
  });
});

test('should render correct message for AF greylist IAF blacklist merchant after submitting L2', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowGB,
    ...ActivationDataPieces.OnboardingMileStoneL2,
    business_type: PROPRIETORSHIP,
    activation_status: 'activated',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(screen.getByText(Messages.INTERNATIONAL_BLACKLIST.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.INTERNATIONAL_BLACKLIST.description)).toBeInTheDocument();
  });
});

test('should render correct message for AF greylist IAF greylist merchant after submitting L1', async () => {
  ActivationDB.update({
    activation_flow: 'greylist',
    activation_status: 'activated',
    submitted: true,
    business_type: PROPRIETORSHIP,
  });
  render(<App />, {});
  await waitForLoadingToFinish();

  await waitFor(() => {
    expect(screen.getByText(Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.title)).toBeInTheDocument();
    expect(
      screen.getByText(Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.description),
    ).toBeInTheDocument();
  });
});

test('should render correct message if payment not breached', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.PaymentEnable,
    ...ActivationDataPieces.regBusinessOverview,
    ...ActivationDataPieces.OnboardingMileStoneL1,
  });
  PaymentEscalationDB.update({
    amount: '1000000',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(screen.getByText(Messages.PAYMENT_ESCALATION.not_breach)).toBeInTheDocument();
  });
});

test('should not render payment escalation card if payment is disable and not breached', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.OnboardingMileStoneL1,
  });
  PaymentEscalationDB.update({
    amount: '900000',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  await waitFor(() => {
    expect(() => screen.getByText(Messages.PAYMENT_ESCALATION.not_breach)).toThrow();
  });
});
