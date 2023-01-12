import { render, screen, waitFor, server } from 'common/services/test/test-utils';
import OTPModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/OTPModal';
import * as analytics from 'common/utils/analytics';
import userEvent from '@testing-library/user-event';
import { OTPMETHOD } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/services';
import { rest } from 'msw';

describe('OTP Modal', () => {
  const defaultProps = {
    otpMethod: OTPMETHOD.EMAIL,
    email: 'test@razorpay.com',
    screen: 'test screen',
    otpAuthToken: 'test OAuth token',
    onSubmit: jest.fn(),
    onClose: jest.fn(),
    onOTPCompleteCallback: jest.fn(),
  };

  const phoneNumberProps = {
    otpMethod: OTPMETHOD.PHONE,
    phone: '9999999999',
  };

  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');

  const App = (props) => {
    return <OTPModal {...defaultProps} {...props} />;
  };

  const setupPhoneNumberApp = async () => {
    render(<App {...phoneNumberProps} />);
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: 'add email 2fa otp',
        actionName: 'sent',
        screen: defaultProps.screen,
        properties: {
          result: 'Success',
        },
      });
    });
  };

  const typeOtp = async (otp) => {
    const otpInputElements = screen.getAllByRole('spinbutton');
    await userEvent.type(otpInputElements[0], otp);
  };
  const typeOtpAndVerifySuccessfulConfirmation = async (otpMethod) => {
    await typeOtp('123456');
    const confirmButton = screen.getByRole('button', { name: 'Confirm' });
    expect(confirmButton).toBeEnabled();
    await userEvent.click(confirmButton);
    const verifyingButton = screen.getByRole('button', { name: 'Verifying...' });
    expect(verifyingButton).toBeInTheDocument();
    expect(verifyingButton).toBeDisabled();

    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: otpMethod === OTPMETHOD.PHONE ? 'add email 2fa otp' : 'add email',
        actionName: 'verify',
        screen: defaultProps.screen,
        properties: {
          result: 'Success',
        },
      });
      expect(defaultProps.onSubmit).toHaveBeenCalled();
      expect(defaultProps.onOTPCompleteCallback).toHaveBeenCalled();
    });
  };

  const typeIncorrectOtpAndVerifyApiMessage = async (otpMethod) => {
    await typeOtp('000000');
    await userEvent.click(screen.getByRole('button', { name: 'Confirm' }));

    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: otpMethod === OTPMETHOD.PHONE ? 'add email 2fa otp' : 'add email',
        actionName: 'verify',
        screen: defaultProps.screen,
        properties: {
          result: 'Failure',
          failureMessage: 'incorrect-otp',
        },
      });
      expect(screen.getByText(/incorrect-otp/i)).toBeInTheDocument();
    });
  };

  const typeIncorrectOtpAndVerifyNoApiMessage = async (otpMethod) => {
    await typeOtp('111111');
    await userEvent.click(screen.getByRole('button', { name: 'Confirm' }));

    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: otpMethod === OTPMETHOD.PHONE ? 'add email 2fa otp' : 'add email',
        actionName: 'verify',
        screen: defaultProps.screen,
        properties: {
          result: 'Failure',
          failureMessage: null,
        },
      });
      expect(screen.getByText(/Some error occured. Please refresh/i)).toBeInTheDocument();
    });
  };

  beforeEach(() => {
    analyticsTrackMock.mockClear();
    defaultProps.onSubmit.mockClear();
    defaultProps.onOTPCompleteCallback.mockClear();
  });

  test('should render OTP Modal content of otp method email', () => {
    render(<App />);
    expect(screen.getByText('Confirm your Email')).toBeInTheDocument();
    expect(
      screen.getByText('An email with a 6-digit OTP has been sent to your new email address'),
    ).toBeInTheDocument();
    expect(screen.getByText(defaultProps.email)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Resend' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Confirm' })).toBeDisabled();
  });

  test('should type OTP, submit and verify successful confirmation when otp method is email', async () => {
    render(<App />);
    await typeOtpAndVerifySuccessfulConfirmation(OTPMETHOD.EMAIL);
  });

  test('should type incorrect OTP, submit and show error notification of the api when otp method is email', async () => {
    render(<App />);
    await typeIncorrectOtpAndVerifyApiMessage(OTPMETHOD.EMAIL);
  });

  test('should type incorrect OTP, submit and show Some error occured. Please refresh message when otp method is email', async () => {
    render(<App />);
    await typeIncorrectOtpAndVerifyNoApiMessage(OTPMETHOD.EMAIL);
  });

  test('should render OTP Modal content of otp method email', async () => {
    await setupPhoneNumberApp();
    expect(screen.getByText('Verify your Phone Number')).toBeInTheDocument();
    expect(
      screen.getByText('A SMS with a 6-digit OTP has been sent to your registered phone number'),
    ).toBeInTheDocument();
    expect(screen.getByText(`+91-${phoneNumberProps.phone}`)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Resend' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Confirm' })).toBeDisabled();
  });

  test('should type OTP, submit and verify successful confirmation when otp method is phone number', async () => {
    await setupPhoneNumberApp();
    await typeOtpAndVerifySuccessfulConfirmation(OTPMETHOD.PHONE);
  });

  test('should type incorrect OTP, submit and show error notification of the api when otp method is phone number', async () => {
    await setupPhoneNumberApp();
    await typeIncorrectOtpAndVerifyApiMessage(OTPMETHOD.PHONE);
  });

  test('should type incorrect OTP, submit and show Some error occured. Please refresh message when otp method is phone number', async () => {
    await setupPhoneNumberApp();
    await typeIncorrectOtpAndVerifyNoApiMessage(OTPMETHOD.PHONE);
  });

  test('should send OTP on mount when otp method is phone number and also on clicking Resend', async () => {
    await setupPhoneNumberApp();
    await userEvent.click(screen.getByRole('button', { name: 'Resend' }));
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: 'add email 2fa otp',
        actionName: 'sent',
        screen: defaultProps.screen,
        properties: {
          result: 'Success',
        },
      });
    });

    server.use(
      rest.post('*/merchant/api/live/users/otp/send', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({ status_code: 200, success: false, errors: ['test-resend-error'] }),
          ctx.delay(50),
        );
      }),
    );

    await userEvent.click(screen.getByRole('button', { name: 'Resend' }));
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenLastCalledWith({
        objectName: 'add email 2fa otp',
        actionName: 'sent',
        screen: defaultProps.screen,
        properties: {
          result: 'Failure',
          failureMessage: 'test-resend-error',
        },
      });
      expect(screen.getByText('test-resend-error')).toBeInTheDocument();
    });

    server.use(
      rest.post('*/merchant/api/live/users/otp/send', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({ status_code: 200, success: false, errors: null }),
          ctx.delay(50),
        );
      }),
    );

    await userEvent.click(screen.getByRole('button', { name: 'Resend' }));
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenLastCalledWith({
        objectName: 'add email 2fa otp',
        actionName: 'sent',
        screen: defaultProps.screen,
        properties: {
          result: 'Failure',
          failureMessage: null,
        },
      });
      expect(screen.getByText('Some error occured. Please refresh')).toBeInTheDocument();
    });
  });

  test('should send OTP on clicking send when otp method is phone email', async () => {
    const { rerender } = render(<App />);
    await userEvent.click(screen.getByRole('button', { name: 'Resend' }));
    // add test case for verifying successful otp generation

    rerender(<App email="error@razorpay.com" />);
    await userEvent.click(screen.getByRole('button', { name: 'Resend' }));
    await waitFor(() => {
      expect(screen.getByText(/error-email/i)).toBeInTheDocument();
    });
  });

  test('should show Change button when reset callback is provided', async () => {
    const resetFn = jest.fn();
    render(<App reset={resetFn} />);
    const changeButton = screen.getByRole('button', { name: 'Change' });
    expect(changeButton).toBeInTheDocument();
    await userEvent.click(changeButton);
    expect(resetFn).toHaveBeenCalled();
  });

  test('should show heading when heading is provided', () => {
    render(<App heading="test-heading" />);
    expect(screen.getByText('test-heading')).toBeInTheDocument();
  });

  test('should call onClose when modal close', async () => {
    render(<App />);
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    expect(defaultProps.onClose).toHaveBeenCalled();
  });
});
