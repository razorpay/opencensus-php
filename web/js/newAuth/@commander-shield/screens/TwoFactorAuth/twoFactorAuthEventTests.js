import twoFactorAuthEvents from './twoFactorAuthEvents';

export const twoFactorAuthEventTests = {
  trackInitiate: (email) => {
    expect(twoFactorAuthEvents.trackInitiate).toHaveBeenCalledWith({ email });
  },
  trackFailure: (email, error) => {
    expect(twoFactorAuthEvents.trackFailure).toHaveBeenCalledWith({ email, error });
  },
  trackResendOtpInitiate: (email) => {
    expect(twoFactorAuthEvents.trackResendOtpInitiate).toHaveBeenCalledWith({ email });
  },
  trackResendOtpSuccess: (email) => {
    expect(twoFactorAuthEvents.trackResendOtpSuccess).toHaveBeenCalledWith({ email });
  },
  trackSuccess: (email) => {
    expect(twoFactorAuthEvents.trackSuccess).toHaveBeenCalledWith({ email });
  },
};
