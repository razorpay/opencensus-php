import { authMethods } from '../screenHelpers';
import signInEvents from './signInEvents';

export const signInEventTests = {
  trackSignInInitiate: (method = authMethods.EMAIL) => {
    expect(signInEvents.trackSignInInitiate).toHaveBeenCalledWith({ method });
  },
  trackCaptchaSuccess: (captchaMode, method, isCaptchaV3ValidationFailed) => {
    expect(signInEvents.trackCaptchaSuccess).toHaveBeenCalledWith({
      captchaMode,
      isCaptchaV3ValidationFailed,
      method,
    });
  },
  bindSegmentUserIdentity: (user) => {
    expect(signInEvents.bindSegmentUserIdentity).toHaveBeenCalledWith(user);
  },
  trackSignInSuccess: (email, method, id, mid) => {
    expect(signInEvents.trackSignInSuccess).toHaveBeenCalledWith({
      email,
      method,
      userId: id,
      mid,
    });
  },
  trackCaptchaV3Failure: (email, error, method) => {
    expect(signInEvents.trackCaptchaV3Failure).toHaveBeenCalledWith({
      email,
      error,
      method,
    });
  },
  trackResendOtpInitiate: () => {
    expect(signInEvents.trackResendOtpInitiate).toHaveBeenCalled();
  },
  trackResendOtpSuccess: () => {
    expect(signInEvents.trackResendOtpSuccess).toHaveBeenCalled();
  },
  trackSignInFailure: (email, error, actualError, method) => {
    expect(signInEvents.trackSignInFailure).toHaveBeenCalledWith({
      email,
      error,
      actualError,
      method,
    });
  },
  trackNonSignInActionsInitiate: (action, method = authMethods.EMAIL) => {
    expect(signInEvents.trackNonSignInActionsInitiate).toHaveBeenCalledWith({
      action,
      method,
    });
  },
};
