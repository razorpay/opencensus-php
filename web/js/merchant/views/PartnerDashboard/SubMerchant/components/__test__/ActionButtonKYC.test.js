import '@testing-library/jest-dom/extend-expect';
import moment from 'moment';

import ActionButtonKYC from 'merchant/views/PartnerDashboard/SubMerchant/components/ActionButtonKYC';
import * as analyticsUtil from 'merchant/views/PartnerDashboard/SubMerchant/components/utils/analytics';
import * as navigationUtil from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent } from 'test-utils';
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
const commonProps = {
  productType,
  submerchant: { id: 'acc_LY0LBrSgJLlFHa', details: { activation_status: null }, kyc_access: null },
  showNotification: jest.fn(),
  isPGProductWithInviteFlow: false,
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
    return render(<ActionButtonKYC {...props} />);
  };
  test("Partner didn't request SubM", () => {
    render(<ActionButtonKYC {...commonProps} />);
    expect(screen.getByText('Request for KYC')).toBeInTheDocument();
  });

  test('SubM  approval pending', () => {
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

  test('SubM didnt take action on request and request expired', () => {
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

  test('SubM rejected request 1 time', () => {
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

  test('SubM rejected request 2 time', () => {
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

  test('SubM rejected request 3 time', () => {
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

    expect(screen.queryByText('Perform KYC')).toBeNull();
    expect(screen.getByTestId('component-wrapper').firstChild).not.toBeInTheDocument();
  });

  test('Status is instantly_activated by Razorpay', () => {
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

  test('should trigger Resend KYC request successfully for PG Invite Flow', async () => {
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
});
