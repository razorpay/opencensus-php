import React from 'react';

import { STEPS } from 'newAuth/signup/Constants';
import WelcomeBack from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/WelcomeBack';
import { render, screen, userEvent, waitFor } from 'test-utils';

const defaultProps = {
  setMobileNumber: () => {},
  setStep: () => {},
  mobileNumber: '8888888888',
};

describe('WelcomeBack', () => {
  const renderApp = (props) => render(<WelcomeBack {...defaultProps} {...props} />);

  test('should render welcome back screen', () => {
    renderApp();
    expect(screen.getByText(/Welcome Back/i)).toBeInTheDocument();
    expect(screen.getByText(/Login Now/i)).toBeInTheDocument();
  });

  test('should have mobile number prefilled', () => {
    renderApp();
    const inputElement = screen.getByRole('textbox', { name: 'Phone Number' });
    expect(inputElement).toHaveValue('8888888888');
  });

  test('should take to signup page when clicking on sign up link', async () => {
    const setStep = jest.fn();
    renderApp({ setStep });
    const signUpLink = screen.getByRole('link', { name: 'Sign Up' });
    await waitFor(() => {
      userEvent.click(signUpLink);
    });

    await waitFor(() => {
      expect(setStep).toHaveBeenCalled();
      expect(setStep).toHaveBeenCalledWith(expect.any(Function));
    });

    const setStepFunction = setStep.mock.calls[0][0];
    await waitFor(() => {
      expect(setStepFunction(STEPS.WELCOME_BACK)).toBe(STEPS.MOBILE_NUMBER);
    });
  });
});
