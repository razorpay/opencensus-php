import forgotPasswordEvents from './forgotPasswordEvents';

export const forgotPasswordEventTests = {
  trackSubmitInitiate: () => {
    expect(forgotPasswordEvents.trackSubmitInitiate).toHaveBeenCalled();
  },

  trackSubmitSuccess: () => {
    expect(forgotPasswordEvents.trackSubmitSuccess).toHaveBeenCalled();
  },
};
