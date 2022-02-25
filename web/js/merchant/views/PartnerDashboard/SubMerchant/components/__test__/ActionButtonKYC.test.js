import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import ActionButtonKYC from '../ActionButtonKYC';
import { BrowserRouter as Router } from 'react-router-dom';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import moment from 'moment';

describe('<ActionButtonKYC /> ', () => {
  test("Partner didn't request SubM", () => {
    render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC />
        </ThemeProvider>
      </Router>,
    );
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.getByText('Rejected Multiple times')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.queryByText('Perform KYC')).toBeNull();
    expect(container.firstChild).toBe(null);
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
    const { container } = render(
      <Router>
        <ThemeProvider theme={theme}>
          <ActionButtonKYC {...props} />
        </ThemeProvider>
      </Router>,
    );
    expect(screen.queryByText('Resend KYC request')).toBeInTheDocument();
    const isButtonDisabled = container.firstChild.classList.contains('action-kyc-request-disable');
    expect(isButtonDisabled).toBe(false);
  });
});
