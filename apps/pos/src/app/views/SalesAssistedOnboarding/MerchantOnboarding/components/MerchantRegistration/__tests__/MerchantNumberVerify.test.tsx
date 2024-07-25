import React from 'react';
import MerchantNumberVerify from '../MerchantNumberVerify';
import { render, screen, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  OTPInput: ({ value, onChange }) => (
    <input
      value={value}
      onChange={(e) => onChange({ value: e.target.value })}
      data-testid="otp-input-field"
    />
  ),
}));

const initProps = {
  isOTPSent: false,
  error: null,
  OTPToken: '',
  isOTPLoading: false,
  isVerifyOTPLoading: false,
  isSwitchMerchantLoading: false,
  handleOnPhoneNumberConfirm: jest.fn(),
  handleOnOTPSubmit: jest.fn(),
  onPhoneNumberChange: jest.fn(),
  onOTPChange: jest.fn(),
};

const renderApp = (props = {}) => {
  const defaulltProps = {
    ...initProps,
    ...props,
  };
  render(<MerchantNumberVerify {...defaulltProps} />);
};

describe('MerchantNumberVerify', () => {
  test('should render MerchantNumberVerufy component and trigger handleOnPhoneNumberConfirm on number confirm', async () => {
    renderApp();
    expect(screen.getByText(/Let's get merchant's mobile number verified/)).toBeInTheDocument();
    await userEvent.type(screen.getByRole('textbox'), '1234567890');
    await userEvent.click(screen.getByText('Verify'));
    await waitFor(() => {
      expect(initProps.handleOnPhoneNumberConfirm).toHaveBeenCalledWith('1234567890');
    });
    expect(initProps.onPhoneNumberChange).toHaveBeenLastCalledWith('1234567890');
  });

  test('should render OTP input and call OTP verify once submitted', async () => {
    renderApp({ isOTPSent: true, OTPToken: 'abc_1233' });
    expect(screen.getByText(`Didn't receive OTP?`)).toBeInTheDocument();
    await userEvent.type(screen.getByTestId('otp-input-field'), '000007');
    await userEvent.click(screen.getByText('Submit OTP'));
    await waitFor(() => {
      expect(initProps.handleOnOTPSubmit).toHaveBeenCalledWith(
        expect.objectContaining({ otp: '000007', token: 'abc_1233' }),
      );
    });
    expect(initProps.onOTPChange).toHaveBeenLastCalledWith('000007');
  });

  test('should show error message if passed', () => {
    renderApp({ error: 'Invalid OTP' });
    expect(screen.getByText('Invalid OTP')).toBeInTheDocument();
  });

  test('should show kyc screen loader when isSwitchMerchantLoading is true', () => {
    renderApp({ isSwitchMerchantLoading: true });
    expect(screen.getByText('Redirecting you to the onboarding journey....')).toBeInTheDocument();
  });
});
