// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
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
import { render, screen, waitFor } from 'test-utils';

beforeEach(() => {
  ActivationDB.reset();
  InternationalWorkflowDB.reset();
  WebsiteWorkflowDB.reset();
  PaymentEscalationDB.reset();
});

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

jest.mock('merchant/views/onboarding/mobile/hooks/useActivation');
jest.mock('merchant/views/onboarding/mobile/hooks/useEscalation');

describe('<AcceptPaymentsCard />', () => {
  test('should render correct message for AF whitelist IAF greylist merchant after completing activation', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.ActivationFlowWG,
        ...ActivationDataPieces.OnboardingMileStoneL2,
        activation_status: 'activated',
        business_type: PROPRIETORSHIP,
      }),
    });
    useEscalation.mockReturnValue({
      status: 'success',
    });

    const mockUseQuery = jest.fn(({ queryKey }) => {
      const [queryKeyString] = queryKey;
      switch (queryKeyString) {
        case 'internationalWorkflowStatus':
          return { status: '', data: InternationalWorkflowDB.read() };
        case 'websiteWorkflowStatus':
          return { status: '', data: WebsiteWorkflowDB.read() };
        default:
          return {};
      }
    });

    // eslint-disable-next-line
    require('@tanstack/react-query').useQuery = mockUseQuery;
    render(<AcceptPaymentsCard />, {});

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
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.ActivationFlowWW,
        ...ActivationDataPieces.OnboardingMileStoneL1,
        business_type: PROPRIETORSHIP,
      }),
    });
    useEscalation.mockReturnValue({
      status: 'success',
    });

    const mockUseQuery = jest.fn(({ queryKey }) => {
      const [queryKeyString] = queryKey;
      switch (queryKeyString) {
        case 'internationalWorkflowStatus':
          return { status: '', data: InternationalWorkflowDB.read() };
        case 'websiteWorkflowStatus':
          return { status: '', data: WebsiteWorkflowDB.read() };
        default:
          return {};
      }
    });

    // eslint-disable-next-line
    require('@tanstack/react-query').useQuery = mockUseQuery;

    render(<AcceptPaymentsCard />, {});

    await waitFor(async () => {
      const ele = await screen.queryByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.title,
      );
      expect(ele).toBeInTheDocument();

      const ele2 = await screen.queryByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.description,
      );
      expect(ele2).toBeInTheDocument();
    });
  });

  test('should render correct message for AF whitelist IAF whitelist merchant after submitting L1 (has website)', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.ActivationFlowWW,
        ...ActivationDataPieces.OnboardingMileStoneL1,
        business_website: 'https://www.google.com',
        business_type: PROPRIETORSHIP,
      }),
    });

    const mockUseQuery = jest.fn(({ queryKey }) => {
      const [queryKeyString] = queryKey;
      switch (queryKeyString) {
        case 'internationalWorkflowStatus':
          return {
            status: 'success',
            data: InternationalWorkflowDB.update({
              payment_gateway: 'approved',
            }),
          };
        case 'websiteWorkflowStatus':
          return { status: 'success', data: WebsiteWorkflowDB.read() };
        default:
          return {};
      }
    });

    // eslint-disable-next-line
    require('@tanstack/react-query').useQuery = mockUseQuery;

    render(<AcceptPaymentsCard testCase="withWebsite" />, {});

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
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.ActivationFlowWW,
        ...ActivationDataPieces.OnboardingMileStoneL2,
        activation_status: 'activated',
        business_type: PROPRIETORSHIP,
      }),
    });

    render(<AcceptPaymentsCard />, {});
    await waitFor(() => {
      expect(
        screen.getByText(
          Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.no_website.title,
        ),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.no_website.description,
        ),
      ).toBeInTheDocument();
    });
  });

  test('should render correct message for AF whitelist IAF whitelist merchant after submitting L2 (has website)', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.ActivationFlowWW,
        ...ActivationDataPieces.OnboardingMileStoneL2,
        business_website: 'https://www.google.com',
        activation_status: 'activated',
        business_type: PROPRIETORSHIP,
      }),
    });

    const mockUseQuery = jest.fn(({ queryKey }) => {
      const [queryKeyString] = queryKey;
      switch (queryKeyString) {
        case 'internationalWorkflowStatus':
          return {
            status: 'success',
            data: InternationalWorkflowDB.update({
              payment_gateway: 'approved',
            }),
          };
        case 'websiteWorkflowStatus':
          return { status: 'success', data: WebsiteWorkflowDB.read() };
        default:
          return {};
      }
    });

    // eslint-disable-next-line
    require('@tanstack/react-query').useQuery = mockUseQuery;

    render(<AcceptPaymentsCard testCase="L2HasWebsite" />, {});

    await waitFor(async () => {
      const ele = await screen.queryByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.title,
      );
      expect(ele).toBeInTheDocument();

      const ele2 = await screen.queryByText(
        Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.description,
      );
      expect(ele2).toBeInTheDocument();
    });
  });

  test('should render correct message for AF greylist IAF blacklist merchant after submitting L2', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.ActivationFlowGB,
        ...ActivationDataPieces.OnboardingMileStoneL2,
        business_type: PROPRIETORSHIP,
        activation_status: 'activated',
      }),
    });

    render(<AcceptPaymentsCard />, {});
    await waitFor(() => {
      expect(screen.getByText(Messages.INTERNATIONAL_BLACKLIST.title)).toBeInTheDocument();
      expect(screen.getByText(Messages.INTERNATIONAL_BLACKLIST.description)).toBeInTheDocument();
    });
  });

  test('should render correct message for AF greylist IAF greylist merchant after submitting L1', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        activation_flow: 'greylist',
        activation_status: 'activated',
        submitted: true,
        business_type: PROPRIETORSHIP,
      }),
    });
    render(<AcceptPaymentsCard />, {});

    await waitFor(() => {
      expect(screen.getByText(Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.title)).toBeInTheDocument();
      expect(
        screen.getByText(Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.description),
      ).toBeInTheDocument();
    });
  });

  test('should render correct message if payment not breached', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.PaymentEnable,
        ...ActivationDataPieces.regBusinessOverview,
        ...ActivationDataPieces.OnboardingMileStoneL1,
      }),
    });

    useEscalation.mockReturnValue({
      status: 'success',
      data: {
        amount: '1000000',
      },
    });
    render(<AcceptPaymentsCard />, {});
    await waitFor(() => {
      expect(screen.getByText(Messages.PAYMENT_ESCALATION.not_breach)).toBeInTheDocument();
    });
  });

  test('should not render payment escalation card if payment is disable and not breached', async () => {
    useActivation.mockReturnValue({
      status: 'success',
      data: ActivationDB.update({
        ...ActivationDataPieces.OnboardingMileStoneL1,
      }),
    });

    useEscalation.mockReturnValue({
      status: 'success',
      data: {
        amount: '900000',
      },
    });
    render(<AcceptPaymentsCard />, {});
    await waitFor(() => {
      expect(() => screen.getByText(Messages.PAYMENT_ESCALATION.not_breach)).toThrow();
    });
  });
});
