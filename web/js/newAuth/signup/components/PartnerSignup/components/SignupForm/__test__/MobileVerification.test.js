import React from 'react';

import MobileVerification from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/MobileVerification';
import { STEPS } from 'newAuth/signup/Constants';
import { render, screen, userEvent } from 'test-utils';

// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  mobileNumber: '8888888888',
  otpVerifyToken: 'verify token',
  setOtpVerifyToken: () => {},
  isSendWhatsapp: false,
  setShowHeader: () => {},
  setStep: () => {},
};

describe('MobileVerification', () => {
  const renderApp = (props) => render(<MobileVerification {...defaultProps} {...props} />);

  test('should render mobile verification screen', async () => {
    const setStep = jest.fn();
    renderApp({ setStep });
    await userEvent.click(screen.getByText('Change'));
    expect(screen.getByText(/OTP Successfully Sent/i)).toBeInTheDocument();
    expect(setStep).toHaveBeenCalledWith(STEPS.MOBILE_NUMBER);
  });
});
