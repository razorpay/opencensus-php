import accountBlockEvents from './accountBlockEvents';

export const accountBlockEventTests = {
  trackSuccess: (email, method) => {
    expect(accountBlockEvents.trackSuccess).toHaveBeenCalledWith({ email, method });
  },
  trackInitiated: (email, method, flow) => {
    expect(accountBlockEvents.trackInitiated).toHaveBeenCalledWith({ email, method, flow });
  },
};
