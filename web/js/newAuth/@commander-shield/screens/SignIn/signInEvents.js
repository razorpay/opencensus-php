import trackEvents from '../../js/analytics';
import { getCaptchaVariant } from '../../utils/captchaService';
import { getAttribUtmData } from '../../utils/getUtmData';
import {
  sendSignInInitiatedEvent,
  sendSignInFailureEvent,
  sendSignInSuccessEvent,
} from '../../js/signInAnalytics';
import { authMethods } from '../screenHelpers';

const utmData = getAttribUtmData();

const signInEvents = {
  // fire when user lands on signin page
  trackPageLoad: () => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_landed' });

    sendSignInSuccessEvent('landed');
    sendSignInSuccessEvent('display_login');
  },

  // fire before making an api call to `/register` or `/oauth-register`
  trackSignInInitiate: ({ email, method, googleAuthVariant }) => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_initiate' });

    sendSignInInitiatedEvent('login', {
      emailId: email,
      method,
      googleAuthVariant,
    });
  },

  trackCreateAccountInitiate: ({ email, method, googleAuthVariant }) => {
    sendSignInInitiatedEvent('create_account', {
      emailId: email,
      method,
      googleAuthVariant,
    });
  },

  trackCreateAccountSuccess: ({
    email,
    method,
    googleAuthVariant,
    mid,
    easyOnboardingProperties = {},
  }) => {
    sendSignInSuccessEvent('create_account', {
      emailId: email,
      method,
      googleAuthVariant,
      mid,
      ...easyOnboardingProperties,
    });
    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Create Account Result',
      screen: 'home page',
      properties: {
        status: 'success',
        mid,
        ...easyOnboardingProperties,
      },
      toCleverTap: true,
    });
  },

  trackCreateAccountFailure: ({
    email,
    method,
    googleAuthVariant,
    error,
    easyOnboardingProperties = {},
  }) => {
    sendSignInFailureEvent('create_account', {
      emailId: email,
      method,
      googleAuthVariant,
      error,
      ...easyOnboardingProperties,
    });
    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Create Account Result',
      screen: 'home page',
      properties: {
        status: 'failure',
        error,
        ...easyOnboardingProperties,
      },
    });
  },

  trackSignInNativeInitiate: ({ email, captchaVariant, method = authMethods.EMAIL }) => {
    sendSignInInitiatedEvent('native_auth', {
      emailId: email,
      captcha_variant: captchaVariant,
      method,
    });
  },

  trackSignInNativeFailure: ({ error }) => {
    sendSignInFailureEvent('native_auth', {
      error,
      method: authMethods.PHONE_NUMBER,
    });
  },

  // fire after the api call is successful and user is about to transition
  // to dashboard
  trackSignInSuccess: ({ email, method, googleAuthVariant, userId, mid }) => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_success' });

    sendSignInSuccessEvent('login', {
      emailId: email,
      method,
      userId,
      mid,
      googleAuthVariant,
      source: 'sign_in',
    });
  },

  trackOneTapClose: () => {
    sendSignInInitiatedEvent('google_onetap_close');
  },

  trackGoogleAccountNotFoundSuccess: ({ email, method, googleAuthVariant }) => {
    sendSignInSuccessEvent('account_not_found_modal', {
      emailId: email,
      method,
      googleAuthVariant,
    });
  },

  trackSignInFailure: ({ email, error, actualError, method, googleAuthVariant }) => {
    sendSignInFailureEvent('login', {
      emailId: email,
      method,
      googleAuthVariant,
      error,
      actualError,
    });
  },
  // on click of Retry Login on Create a new account popup
  trackTryAnotherAccountPopInitiated: () => {
    sendSignInInitiatedEvent('try_another_account');
  },

  trackGoogleAuthInitiate: ({ googleAuthVariant, oneTapHideReason }) => {
    sendSignInInitiatedEvent('google_oauth', {
      googleAuthVariant,
      oneTapHide: oneTapHideReason,
    });
  },

  trackGoogleAuthSuccess: ({ email, googleAuthVariant }) => {
    sendSignInSuccessEvent('google_oauth', {
      emailId: email,
      googleAuthVariant,
    });
  },

  trackGoogleAuthFailure: ({ email, googleAuthVariant, error }) => {
    sendSignInFailureEvent('google_oauth', {
      emailId: email,
      googleAuthVariant,
      error,
    });
  },

  trackDisplayGoogleAuthSuccess: ({ googleAuthVariant, oneTapHideReason }) => {
    sendSignInSuccessEvent('display_google_auth', {
      googleAuthVariant,
      oneTapHide: oneTapHideReason,
      source: 'sign_in',
      first_utm: utmData.firstUtm,
      last_utm: utmData.lastUtm,
      first_page: utmData.firstPage,
      final_page: utmData.finalPage,
      website: utmData.website,
    });
  },

  trackCaptchaV3Failure: ({ email, error, method }) => {
    sendSignInFailureEvent('captchav3_verification', {
      emailId: email,
      error,
      captcha_variant: getCaptchaVariant(),
      method,
    });
  },

  trackCaptchaFailure: ({ email, method }) => {
    sendSignInFailureEvent('recaptcha', {
      error: 'Could not connect to captcha.',
      emailId: email,
      method,
    });
  },

  trackCaptchaSuccess: ({ captchaMode = 'v2', isCaptchaV3ValidationFailed, method }) => {
    sendSignInSuccessEvent('recaptcha', {
      captcha_verified_by: captchaMode,
      captcha_variant: getCaptchaVariant(),
      called_on_v3_failure: isCaptchaV3ValidationFailed,
      method,
    });
  },

  trackNonSignInActionsInitiate: ({ action, method = authMethods.EMAIL }) => {
    sendSignInInitiatedEvent('non_login_actions', {
      action,
      method,
    });
  },

  trackSignInWithAnotherOptionInitiate: ({ method }) => {
    sendSignInInitiatedEvent('use_another_login_option', { method });
  },

  trackChangeSignInMethodInitiate: ({ mode }) => {
    sendSignInInitiatedEvent('change_login_id', { mode });
  },

  // pass the user id and mid to segment so it can mapped to segment's userId property
  bindSegmentUserIdentity: (user) => {
    trackEvents.segmentIdentify(user.id, { mid: user.mid });
  },

  trackResendOtpInitiate: () => {
    sendSignInInitiatedEvent('resend_otp', { method: authMethods.PHONE_NUMBER });
  },

  trackResendOtpSuccess: () => {
    sendSignInSuccessEvent('resend_otp', { method: authMethods.PHONE_NUMBER });
  },

  trackResendOtpFailure: () => {
    sendSignInFailureEvent('resend_otp', { method: authMethods.PHONE_NUMBER });
  },

  trackGetOtpInitiated: () => {
    sendSignInInitiatedEvent('get_otp', { method: authMethods.PHONE_NUMBER });
  },

  trackGetOtpSuccess: () => {
    sendSignInSuccessEvent('get_otp', { method: authMethods.PHONE_NUMBER });
  },

  trackGetOtpFailure: () => {
    sendSignInFailureEvent('get_otp', { method: authMethods.PHONE_NUMBER });
  },

  trackEasyOnboardingGoogleAuthSignupInitiated: () => {
    const property = {
      type: authMethods.GAUTH,
      merchantCountry: 'IN',
      easyOnboarding: true,
    };

    sendSignInInitiatedEvent('easy_onboarding_google_auth', { ...property });
    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Initiated',
      screen: 'home page',
      properties: {
        status: 'success',
        ...property,
      },
    });
  },
};
export default signInEvents;
