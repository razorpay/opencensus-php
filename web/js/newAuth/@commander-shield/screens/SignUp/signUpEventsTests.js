import { authMethods } from '../screenHelpers';
import signUpEvents from './signUpEvents';

export const signUpEventTests = {
  trackPageLoad: (user) => {
    expect(signUpEvents.trackPageLoad).toHaveBeenCalledWith(user);
  },
  trackSignUpInitiate: (state, method = authMethods.EMAIL) => {
    expect(signUpEvents.trackSignUpInitiate).toHaveBeenCalledWith(
      {
        email: state.user.email,
        partnerIntent: state.user.partnerIntent,
        coupon: state.user.coupon.code,
      },
      method,
    );
  },

  trackSignUpSuccess: (user, state, method) => {
    expect(signUpEvents.trackSignUpSuccess).toHaveBeenCalledWith(
      {
        contact: '',
        ...user.data.user,
      },
      method,
      state.user.partnerIntent,
    );
  },

  bindSegmentUserIdentity: (res, locationQuery) => {
    expect(signUpEvents.bindSegmentUserIdentity).toHaveBeenCalledWith(res.data.user, locationQuery);
  },

  trackCaptchaFailure: ({ email, method = authMethods.EMAIL }) => {
    expect(signUpEvents.trackCaptchaFailure).toHaveBeenCalledWith({
      email,
      error: 'Captcha Failed',
      captchaMode: 'v3',
      method,
    });
  },

  trackCaptchaSuccess: ({
    email,
    captchaMode = 'v3',
    calledOnV3Failure,
    method = authMethods.EMAIL,
  }) => {
    expect(signUpEvents.trackCaptchaSuccess).toHaveBeenCalledWith(
      {
        email,
        captchaMode,
        calledOnV3Failure,
      },
      method,
    );
  },

  trackResendOtpInitiate: () => {
    expect(signUpEvents.trackResendOtpInitiate).toHaveBeenCalled();
  },

  trackResendOtpSuccess: () => {
    expect(signUpEvents.trackResendOtpSuccess).toHaveBeenCalled();
  },

  trackNativeAuthInitiate: (user, { email, method }) => {
    expect(signUpEvents.trackNativeAuthInitiate).toHaveBeenCalledWith(user, { email, method });
  },
};
