import setupTwoFactorAuthEvents from './setupTwoFactorAuthEvents';

export const setupTwoFactorAuthEventTests = {
  trackPageLoad: (email) => {
    expect(setupTwoFactorAuthEvents.trackPageLoad).toHaveBeenCalledWith({ email });
  },
  trackSetupTwoFactorInitiate: (email) => {
    expect(setupTwoFactorAuthEvents.trackSetupTwoFactorInitiate).toHaveBeenCalledWith({ email });
  },
  trackSetupTwoFactorSuccess: (email) => {
    expect(setupTwoFactorAuthEvents.trackSetupTwoFactorSuccess).toHaveBeenCalledWith({ email });
  },
  trackSetupTwoFactorFailure: (email, error) => {
    expect(setupTwoFactorAuthEvents.trackSetupTwoFactorFailure).toHaveBeenCalledWith({
      email,
      error,
    });
  },
};
