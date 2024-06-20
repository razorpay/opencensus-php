import React from 'react';
import {
  getMerchantRegister,
  getMerchantVerifyResponse,
  getSwitchMerchantResponse,
} from './mocks/handlers';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import AddMerchant from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard/AddMerchant';
import * as salesAssistedOnboardingAPIs from 'apps/pos/src/app/apis/SalesAssistedOnboarding';
import * as useScreen from 'apps/pos/src/app/utils/hooks/useScreen';

jest.mock('@razorpay/blade/components', () => {
  const bladeActual = jest.requireActual('@razorpay/blade/components');
  return {
    __esModule: true,
    ...bladeActual,
    OTPInput: ({ label, value, errorText, onChange }) => (
      <div>
        <label>{label}</label>
        <input value={value} onChange={(e) => onChange({ value: e.target.value })} />
        <p>{errorText}</p>
      </div>
    ),
  };
});

const renderApp = () => {
  render(<AddMerchant />);
};

describe('<AddMerchant/>', () => {
  beforeAll(() => {
    const location = {
      ...window.location,
      assign: jest.fn(),
    };
    Object.defineProperty(window, 'location', {
      value: location,
    });
  });
  test('should render AddMerchant button and onClick should render Enter phone number', async () => {
    renderApp();
    expect(screen.getByText('Add Merchant')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Add Merchant'));
    expect(screen.getByText('Create a New Merchant')).toBeInTheDocument();
    expect(screen.getByText(`Let's get merchant's mobile number verified`)).toBeInTheDocument();
    expect(screen.getByText(`Verify & Send OTP`)).toBeInTheDocument();
  });

  test('should render AddMerchant button and onClick should render Enter phone number', async () => {
    const apiSpy = jest.spyOn(salesAssistedOnboardingAPIs, 'registerMerchant');
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(apiSpy).toHaveBeenCalledWith({ contactMobile: '7578967104', mockSend: true });
    });
  });

  test('should show invalid phone number error if invalid number entered', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '0000222200000');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Please enter a valid phone number')).toBeInTheDocument();
    });
    expect(screen.getByRole('button', { name: 'Verify & Send OTP' })).toBeDisabled();
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    expect(screen.getByRole('button', { name: 'Verify & Send OTP' })).toBeEnabled();
  });

  test('should move to the OTP enter step with correct content if the number is correct and unique', async () => {
    server.use(getMerchantVerifyResponse({ type: 'success' }));
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Enter OTP sent to +917578967104')).toBeInTheDocument();
    });
    expect(screen.getByRole('button', { name: 'Validate OTP' })).toBeDisabled();
    expect(screen.getByText('Validate OTP')).toBeInTheDocument();
    expect(screen.getByText('Back')).toBeInTheDocument();
  });

  test('should show error if the merchant already exsit in the system', async () => {
    server.use(getMerchantVerifyResponse({ type: 'duplicateUser' }));
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(
        screen.getByText(
          `This service is for new accounts only. We're working on bringing it to existing accounts soon!`,
        ),
      ).toBeInTheDocument();
    });
  });

  test('should show error if OTP request failed', async () => {
    server.use(getMerchantVerifyResponse({ type: 'error' }));
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Some error occured')).toBeInTheDocument();
    });
  });

  test('should trigger verify OTP API on entering OTP and redirect to easy dashboard on success', async () => {
    const apiSpy = jest.spyOn(salesAssistedOnboardingAPIs, 'verifyMerchantOTP');
    server.use(
      getMerchantRegister({ type: 'success' }),
      getMerchantVerifyResponse({ type: 'success' }),
      getSwitchMerchantResponse({ type: 'success' }),
    );
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Enter OTP sent to +917578967104')).toBeInTheDocument();
    });
    await userEvent.type(screen.getByRole('textbox'), '000007');
    await userEvent.click(screen.getByText('Validate OTP'));
    expect(apiSpy).toHaveBeenCalledWith({
      contactMobile: '7578967104',
      mockSend: true,
      otp: '000007',
      token: 'abc1234',
    });
    await waitFor(() => {
      expect(screen.getByText('Redirecting you to the onboarding journey....')).toBeInTheDocument();
    });
  });

  test('should show error if verify OTP fails and clears OTP', async () => {
    server.use(
      getMerchantVerifyResponse({ type: 'success' }),
      getMerchantRegister({ type: 'error' }),
    );
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Enter OTP sent to +917578967104')).toBeInTheDocument();
    });
    await userEvent.type(screen.getByRole('textbox'), '000007');
    await userEvent.click(screen.getByText('Validate OTP'));
    await waitFor(() => {
      expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    });
  });

  test('should clear OTP and retain phone number on clicking on back from enter OTP screen', async () => {
    server.use(getMerchantVerifyResponse({ type: 'success' }));
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Enter OTP sent to +917578967104')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByText('Back'));
    await waitFor(() => {
      expect(screen.getByRole('textbox')).toHaveValue('7578967104');
    });
  });

  test('should render the screen in bottom sheet if mobile', async () => {
    const useScreenSpy = jest.spyOn(useScreen, 'useScreen');
    useScreenSpy.mockReturnValue({ isMobile: true });
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    expect(screen.getByText('Create a New Merchant')).toBeInTheDocument();
    expect(screen.getByText(`Let's get merchant's mobile number verified`)).toBeInTheDocument();
    expect(screen.getByText(`Verify & Send OTP`)).toBeInTheDocument();
  });

  test('should trigger switch merchant pos verify OTP API and redirect to easy dashboard on success', async () => {
    const apiSpy = jest.spyOn(salesAssistedOnboardingAPIs, 'verifyMerchantOTP');
    const switchApiSpy = jest.spyOn(salesAssistedOnboardingAPIs, 'switchMerchant');
    server.use(
      getMerchantVerifyResponse({ type: 'success' }),
      getMerchantRegister({ type: 'success' }),
      getSwitchMerchantResponse({ type: 'success' }),
    );
    renderApp();
    await userEvent.click(screen.getByText('Add Merchant'));
    await userEvent.type(screen.getByRole('textbox'), '7578967104');
    await userEvent.click(screen.getByText('Verify & Send OTP'));
    await waitFor(() => {
      expect(screen.getByText('Enter OTP sent to +917578967104')).toBeInTheDocument();
    });
    await userEvent.type(screen.getByRole('textbox'), '000007');
    await userEvent.click(screen.getByText('Validate OTP'));
    expect(apiSpy).toHaveBeenCalledWith({
      contactMobile: '7578967104',
      mockSend: true,
      otp: '000007',
      token: 'abc1234',
    });
    await waitFor(() => {
      expect(screen.getByText('Redirecting you to the onboarding journey....')).toBeInTheDocument();
    });

    expect(switchApiSpy).toHaveBeenCalledWith({ merchantId: 'merchant1234' });
  });
});
