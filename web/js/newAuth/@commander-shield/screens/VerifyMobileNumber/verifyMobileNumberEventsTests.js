import signInEvents from '../SignIn/signInEvents';

const verifyMobileNumberEventsTests = {
  trackChangeSignInMethodInitiate: (mode) => {
    expect(signInEvents.trackChangeSignInMethodInitiate).toHaveBeenCalledWith({ mode });
  },
  trackNonSignInActionsInitiate: (action, method) => {
    expect(signInEvents.trackNonSignInActionsInitiate).toHaveBeenCalledWith({
      action,
      method,
    });
  },
  trackGetOtpInitiated: () => {
    expect(signInEvents.trackGetOtpInitiated).toHaveBeenCalled();
  },
  trackGetOtpSuccess: () => {
    expect(signInEvents.trackGetOtpSuccess).toHaveBeenCalled();
  },
  trackGetOtpFailure: () => {
    expect(signInEvents.trackGetOtpFailure).toHaveBeenCalled();
  },
};

export default verifyMobileNumberEventsTests;
