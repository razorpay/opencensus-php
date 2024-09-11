import React from 'react';
import useMerchantRegistration from '../useMerchantRegistration';
import { render, userEvent, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';
import {
  merchantOtpVerifyHandler,
  merchantRegisterHandler,
} from 'apps/pos/src/services/mocks/handlers/mechantRegistration';
import * as apiRequests from 'apps/pos/src/app/apis/SalesAssistedOnboarding';

const TestApp = () => {
  const mockOtpObject = {
    otp: '000007',
    token: 'mock_token_123',
    contactMobile: '8889909221',
  };
  const {
    error,
    isOTPSent,
    merchantOTPSendData,
    isOTPLoading,
    isVerifyOTPLoading,
    isSwitchMerchantLoading,
    handleOnPhoneNumberConfirm,
    handleOnOTPSubmit,
    triggerRemoveError,
  } = useMerchantRegistration();

  return (
    <div>
      {error ? error : null}
      {isOTPSent ? 'OTP Sent' : null}
      {merchantOTPSendData ? <h1>{merchantOTPSendData.data?.token}</h1> : null}
      {isOTPLoading || isVerifyOTPLoading ? <h1> Loading</h1> : null}
      {isSwitchMerchantLoading ? <h1>Redirecting</h1> : null}
      <button data-testid="confirm-btn" onClick={() => handleOnPhoneNumberConfirm('9709998281')}>
        Confirm Number
      </button>
      <button data-testid="otp-send-btn" onClick={() => handleOnOTPSubmit(mockOtpObject)}>
        Send OTP
      </button>
      <button onClick={() => triggerRemoveError()}>Remove Error</button>
    </div>
  );
};

const renderApp = () => {
  render(<TestApp />);
};

describe('useMerchantRegistration', () => {
  test('should trigger otp request on handleOnPhoneNumberConfirm', async () => {
    server.use(merchantRegisterHandler({ type: 'success' }));
    renderApp();
    await userEvent.click(screen.getByTestId('confirm-btn'));
    expect(screen.getByText('Loading')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText('OTP Sent')).toBeInTheDocument();
    });
  });

  test('should trigger otp send error callback on otp request error', async () => {
    server.use(merchantRegisterHandler({ type: 'failure' }));
    renderApp();
    await userEvent.click(screen.getByTestId('confirm-btn'));
    expect(screen.getByText('Loading')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText('Something went wrong. Please try again')).toBeInTheDocument();
    });
  });

  test('should trigger otp verify success callback on otp verify success', async () => {
    const apiRequestSpy = jest.spyOn(apiRequests, 'verifyMerchantOTP');
    server.use(merchantOtpVerifyHandler({ type: 'success' }));
    renderApp();
    await userEvent.click(screen.getByTestId('otp-send-btn'));
    expect(screen.getByText('Loading')).toBeInTheDocument();
    await waitFor(() => {
      expect(apiRequestSpy).toHaveBeenCalledWith(expect.objectContaining({ otp: '000007' }));
    });
  });

  // TODO: Fix this test
  test.skip('should trigger otp verify error callback on otp verify error', async () => {
    server.use(merchantOtpVerifyHandler({ type: 'failure' }));
    renderApp();
    await userEvent.click(screen.getByTestId('otp-send-btn'));
    expect(screen.getByText('Loading')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    });
  });

  test('should remove error if triggerRemoveError is called', async () => {
    server.use(merchantRegisterHandler({ type: 'failure' }));
    renderApp();
    await userEvent.click(screen.getByTestId('confirm-btn'));
    expect(screen.getByText('Loading')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText('Something went wrong. Please try again')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByText('Remove Error'));
    expect(screen.queryByText('Something went wrong. Please try again')).not.toBeInTheDocument();
  });
});
