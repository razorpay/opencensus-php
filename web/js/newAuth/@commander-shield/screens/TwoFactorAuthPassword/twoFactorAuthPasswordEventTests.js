import twoFactorPasswordAuthEvents from './twoFactorAuthPasswordEvents';

export const twoFactorAuthPasswordEventTests = {
  trackPageLoad: () => {
    expect(twoFactorPasswordAuthEvents.trackPageLoad).toHaveBeenCalled();
  },
  trackInitiate: () => {
    expect(twoFactorPasswordAuthEvents.trackInitiate).toHaveBeenCalled();
  },
  trackSuccess: () => {
    expect(twoFactorPasswordAuthEvents.trackSuccess).toHaveBeenCalled();
  },
  trackFailure: () => {
    expect(twoFactorPasswordAuthEvents.trackFailure).toHaveBeenCalled();
  },
};
