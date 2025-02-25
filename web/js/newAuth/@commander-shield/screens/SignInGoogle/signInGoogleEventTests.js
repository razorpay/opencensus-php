import signInEvents from '../SignIn/signInEvents';
import { getCaptchaVariant } from '../../utils/captchaService';
import featureFlags from '../../utils/featureFlags';
import { authMethods } from '../screenHelpers';

export const signInGoogleEventTests = {
  trackPageLoad: () => {
    expect(signInEvents.trackPageLoad).toHaveBeenCalled();
  },
  trackSignInNativeInitiate: (email, method) => {
    if (featureFlags.ENABLE_MOBILE_OTP_FLOW && method === authMethods.PHONE_NUMBER) {
      expect(signInEvents.trackSignInNativeInitiate).toHaveBeenCalledWith({
        captchaVariant: getCaptchaVariant(),
        method,
      });
    } else {
      expect(signInEvents.trackSignInNativeInitiate).toHaveBeenCalledWith({
        email,
        captchaVariant: getCaptchaVariant(),
        method,
      });
    }
  },
};
