import '@testing-library/jest-dom/extend-expect';
import { act } from '@testing-library/react';
import moment from 'moment';

import ActionButtonKYC from 'merchant/views/PartnerDashboard/SubMerchant/components/ActionButtonKYC';
import * as analyticsUtil from 'merchant/views/PartnerDashboard/SubMerchant/components/utils/analytics';
import * as navigationUtil from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as notifications from 'merchant_common/reducers/notifications';
import { render, screen, userEvent, server, waitFor } from 'test-utils';

import { sendKycRequestError, sendKycRequestSuccess } from './mocks/handlers';
const trackAccountLevelAcceptedInvitesCtaSpy = jest.spyOn(
  analyticsUtil,
  'trackAccountLevelAcceptedInvitesCta',
);
const openKYCFormUtilSpy = jest.spyOn(navigationUtil, 'openKYCFormUtil');

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const productType = PRODUCT_TYPE.PG;

const initialState = {
  session: {
    user: {
      merchant: {},
      isOrgRZP: true,
      isPartner: (partner_type) => partner_type == 'aggregator',
      isPartnerIntent: () => true,
      isFeatureEnabled: () => true,
      findTag: () => true,
      isCommissionInvoicesEnabled: true,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      isOrgAllowedFunctionality: () => false,
    },
  },
};

const commonProps = {
  productType,
  submerchant: { id: 'acc_LY0LBrSgJLlFHa', details: { activation_status: null }, kyc_access: null },
  showNotification: jest.fn(),
};

describe('<ActionButtonKYC /> ', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  const renderApp = ({ activation_status, kyc_access } = {}, experiments = {}) => {
    mockPartnerDashboardExperiments = {
      ...defaultPartnerDashboardExperiments,
      ...experiments,
    };
    const props = {
      ...commonProps,
      submerchant: {
        ...commonProps.submerchant,
        details: { activation_status },
        kyc_access,
      },
    };
    return render(<ActionButtonKYC {...props} />, { initialState });
  };
  const renderAppPos = ({ activation_status, kyc_access } = {}, experiments = {}) => {
    mockPartnerDashboardExperiments = {
      ...defaultPartnerDashboardExperiments,
      ...experiments,
    };
    const props = {
      ...commonProps,
      submerchant: {
        ...commonProps.submerchant,
        details: { activation_status },
        kyc_access,
        pos: {
          activation_status,
        },
      },
      productType: PRODUCT_TYPE.POS,
    };
    return render(<ActionButtonKYC {...props} />);
  };
  test("Partner didn't request SubM", () => {
    render(<ActionButtonKYC {...commonProps} />);
    expect(screen.getByText('Request for KYC')).toBeInTheDocument();
  });

  test.skip('SubM  approval pending', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();

    renderApp({
      kyc_access: {
        state: 'pending_approval',
        rejection_count: 0,
        token_expiry: notExpiredTime,
      },
    });
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(true);
  });

  test.skip('SubM didnt take action on request and request expired', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      kyc_access: {
        state: 'expired',
        rejection_count: 0,
        token_expiry: expiredTime,
      },
    });

    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test.skip('SubM rejected request 1 time', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      kyc_access: {
        state: 'rejected',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    });
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test.skip('SubM rejected request 2 time', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      kyc_access: {
        state: 'rejected',
        rejection_count: 2,
        token_expiry: expiredTime,
      },
    });
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test.skip('SubM rejected request 3 time', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      kyc_access: {
        state: 'rejected',
        rejection_count: 3,
        token_expiry: expiredTime,
      },
    });
    expect(screen.getByText('Rejected Multiple times')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('SubM approved request', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    });
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('KYC request submitted to Razorpay', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      activation_status: 'activated',
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    });

    expect(screen.getByRole('button', { name: 'Perform KYC' })).toBeDisabled();
  });

  test.skip('Status is instantly_activated by Razorpay', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();

    renderApp({
      activation_status: 'instantly_activated',
      kyc_access: {
        state: 'rejected',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    });

    expect(screen.queryByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('should trigger Perform KYC successfully for PG Invite Flow', async () => {
    renderApp(
      {
        kyc_access: {
          state: 'approved',
          rejection_count: 1,
        },
      },
      { isPartnershipsInviteFlowEnabled: true },
    );

    const performKycButton = screen.getByRole('button', { name: 'Perform KYC' });
    await userEvent.click(performKycButton);

    // test tracking
    expect(trackAccountLevelAcceptedInvitesCtaSpy).toHaveBeenCalledWith(
      expect.objectContaining({ id: 'acc_LY0LBrSgJLlFHa' }),
      {
        productType,
        action: 'Perform KYC',
      },
    );

    // test redirection
    expect(openKYCFormUtilSpy).toHaveBeenCalled();
  });

  test.skip('should trigger Resend KYC request successfully for PG Invite Flow', async () => {
    const { history } = renderApp(
      {
        kyc_access: {
          state: 'expired',
          rejection_count: 0,
        },
      },
      { isPartnershipsInviteFlowEnabled: true },
    );

    const resendKycButton = screen.getByRole('button', { name: 'Resend KYC request' });
    await userEvent.click(resendKycButton);

    // test tracking
    expect(trackAccountLevelAcceptedInvitesCtaSpy).toHaveBeenCalledWith(
      expect.objectContaining({ id: 'acc_LY0LBrSgJLlFHa' }),
      {
        productType,
        action: 'Resend KYC request',
      },
    );

    // test redirection
    expect(history.location.pathname).toBe(`/partners/submerchants/acc_LY0LBrSgJLlFHa`);
  });

  test('should render Resubmit KYC details button when POS activation status is NC', () => {
    renderAppPos({ activation_status: 'needs_clarification' });
    expect(screen.getByText('Resubmit KYC details')).toBeInTheDocument();
  });

  test.skip('should trigger KYC request successfully for Aggregator partner', async () => {
    server.use(sendKycRequestSuccess());
    const showNotification = jest.spyOn(notifications, 'showNotification');

    await act(async () => {
      renderApp();

      const requestKycButton = screen.getByRole('button', { name: 'Request for KYC' });
      await userEvent.click(requestKycButton);
    });

    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }));
    });
  });
  test.skip('should trigger KYC request with failure for Aggregator partner', async () => {
    server.use(sendKycRequestError());
    const showNotification = jest.spyOn(notifications, 'showNotification');

    await act(async () => {
      renderApp();

      const requestKycButton = screen.getByRole('button', { name: 'Request for KYC' });

      await userEvent.click(requestKycButton);
    });

    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith(expect.objectContaining({ type: 'error' }));
    });
  });
});
