import { render, screen, userEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import ActionButtonKYC from 'merchant/views/PartnerDashboard/SubMerchant/components/ActionButtonKYC';
import moment from 'moment';
import * as analyticsUtil from 'merchant/views/PartnerDashboard/SubMerchant/components/utils/analytics';
import * as navigationUtil from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
const trackAcceptedInvitesCtaSpy = jest.spyOn(analyticsUtil, 'trackAcceptedInvitesCta');
const openKYCFormUtilSpy = jest.spyOn(navigationUtil, 'openKYCFormUtil');

// TODO: reuse commonProps in existing test cases
const commonProps = {
  activation_status: null,
  trackUserEvent: jest.fn(),
  submerchant: { id: 'acc_LY0LBrSgJLlFHa', details: { activation_status: null } },
  showNotification: jest.fn(),
};

describe('<ActionButtonKYC /> ', () => {
  test("Partner didn't request SubM", () => {
    render(<ActionButtonKYC />);
    expect(screen.getByText('Request for KYC')).toBeInTheDocument();
  });

  test('SubM  approval pending', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'pending_approval',
        rejection_count: 0,
        token_expiry: notExpiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(true);
  });

  test('SubM didnt take action on request and request expired', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'expired',
        rejection_count: 0,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('SubM rejected request 1 time', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'rejected',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('SubM rejected request 2 time', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'rejected',
        rejection_count: 2,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('SubM rejected request 3 time', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'rejected',
        rejection_count: 3,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.getByText('Rejected Multiple times')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('SubM approved request', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: null,
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('KYC request submitted to Razorpay', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: 'activated',
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.queryByText('Perform KYC')).toBeNull();
    expect(screen.getByTestId('component-wrapper').firstChild).not.toBeInTheDocument();
  });

  test('Status is instantly_activated by Razorpay', () => {
    let expiredTime = new Date().getTime() - 100000;
    expiredTime = moment(expiredTime).unix();
    const props = {
      activation_status: 'instantly_activated',
      kyc_access: {
        state: 'rejected',
        rejection_count: 1,
        token_expiry: expiredTime,
      },
    };
    render(<ActionButtonKYC {...props} />);
    expect(screen.queryByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = screen
      .getByTestId('component-wrapper')
      .firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });

  test('should trigger Perform KYC successfully for PG Invite Flow', async () => {
    const props = {
      ...commonProps,
      isPGProductWithInviteFlow: true,
      kyc_access: {
        state: 'approved',
        rejection_count: 1,
      },
    };
    render(<ActionButtonKYC {...props} />);

    const performKycButton = screen.getByRole('button', { name: 'Perform KYC' });
    await userEvent.click(performKycButton);

    // test tracking
    expect(trackAcceptedInvitesCtaSpy).toHaveBeenCalledWith(
      expect.objectContaining({ id: 'acc_LY0LBrSgJLlFHa' }),
      {
        properties: { action: 'Perform KYC' },
      },
    );

    // test redirection
    expect(openKYCFormUtilSpy).toHaveBeenCalled();
  });

  test('should trigger Resend KYC request successfully for PG Invite Flow', async () => {
    const props = {
      ...commonProps,
      isPGProductWithInviteFlow: true,
      kyc_access: {
        state: 'expired',
        rejection_count: 0,
      },
    };
    const { history } = render(<ActionButtonKYC {...props} />);

    const resendKycButton = screen.getByRole('button', { name: 'Resend KYC request' });
    await userEvent.click(resendKycButton);

    // test tracking
    expect(trackAcceptedInvitesCtaSpy).toHaveBeenCalledWith(
      expect.objectContaining({ id: 'acc_LY0LBrSgJLlFHa' }),
      {
        properties: { action: 'Resend KYC request' },
      },
    );

    // test redirection
    expect(history.location.pathname).toBe(`/partners/submerchants/acc_LY0LBrSgJLlFHa`);
  });
});
