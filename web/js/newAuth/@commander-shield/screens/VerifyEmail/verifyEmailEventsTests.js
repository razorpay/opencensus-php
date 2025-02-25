import verifyEmailEvents from './verifyEmailEvents';

const verifyEmailEventsTests = {
  trackInitiate: (user) => {
    expect(verifyEmailEvents.trackVerifyEmailInitiate).toHaveBeenCalledWith(user);
  },
  trackRequest: () => {
    expect(verifyEmailEvents.trackVerifyEmailRequest).toHaveBeenCalled();
  },
  trackSuccess: (user) => {
    expect(verifyEmailEvents.trackVerifyEmailSuccess).toHaveBeenCalledWith(user);
  },
  trackFailure: (user, error) => {
    expect(verifyEmailEvents.trackVerifyEmailError).toHaveBeenCalledWith({ user, error });
  },
  trackResendOtpInitiate: (user) => {
    expect(verifyEmailEvents.trackResendOtpInitiate).toHaveBeenCalledWith(user);
  },
};

export default verifyEmailEventsTests;
