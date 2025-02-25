import errorService from '@razorpay/universe-cli/errorService';
import trackEvents from '../js/analytics';
import {
  COMMON_ENDPOINTS,
  authApiResponse,
  twoFaErrors,
  IGNORE_MESSAGES,
  IGNORE_MESSAGE_ONLY_FOR_USER_API,
} from '../api/apiHelpers';

export const captureSource = {
  CODE: 'code',
  API: 'api',
};

export const sentryFlows = {
  SIGNUP_WITH_EMAIL_PASSWORD: 'signup_email_password',
  SIGNUP_WITH_MOBILE_OTP: 'signup_mobile_otp',
  SIGNUP_WITH_EMAIL_OTP: 'signup_email_otp',
  SIGNUP_WITH_GOOGLE_OAUTH: 'signup_oauth',
  SIGNIN_GOOGLE_ONE_TAP_CALLBACK: 'signin_oauth_one_tap_callback',
  SIGNIN_GOOGLE_BUTTON_ATTACH_HANDLER: 'signin_oauth_button_attack_click_handler',
  SIGNIN_GOOGLE_BUTTON_INIT: 'signin_oauth_button_init',
  SIGNIN_WITH_GOOGLE: 'signin_oauth',
  SIGNIN_WITH_MOBILE_OTP_VERIFY_USER: 'signup_mobile_otp_verify_user',
  SIGNIN_WITH_MOBILE_OTP: 'signin_mobile_otp',
  SIGNIN_WITH_EMAIL_OTP: 'signin_email_otp',
  TWO_FACTOR_PASSWORD: 'two_factor_password_auth',
  TWO_FACTOR_OTP_AUTH: 'two_factor_otp_auth',
  CONTACT_DETAILS: 'contact_details',
  FORGOT_PASSWORD: 'forgot_password',
  SETUP_2FA: 'setup_2fa',
  VERIFY_EMAIL: 'verify_email',
  ERROR_BOUNDARY: 'UNCAUGHT',
  SIGNUP: 'signup_generic',
  SIGNIN: 'signin_generic',
  RESET_PASSWORD: 'resetpassword',
};

const ERROR_CODES_PROMETHEUS = {
  SHIELD_CODE_CAPTURE: 'shield_code_capture',
  SHIELD_API_CODE: 'shield_api_code',
  SHIELD_API_KNOWN: 'shield_api_known',
  SHIELD_API_UNKNOWN: 'shield_api_unknown',
};

const _logErrorToPrometheus = (
  errorDescription,
  flow,
  isApiError = false,
  isKnownApiError = false,
  _captureSource,
) => {
  /**
   * There are 2 segregation of errors which are basically code and API. Based on which we have 4 categories of events
   * which can be used to get insights related to failures on signup/signin. They are:
   * 1. shield_code_capture: represents any error generated from code and is not related to api
   * 2. shield_api_code: represents all known/unknown api generated errors from code but got triggered from some components and not from api interceptors.
   * 3. shield_api_known: represents known api errors triggered directly from api interceptors.
   * 4. shield_api_unknown: represents unknown api errors triggered directly from api interceptors.
   * Example of known api error: email already exists
   */

  let type = ERROR_CODES_PROMETHEUS.SHIELD_CODE_CAPTURE;
  if (isApiError) {
    if (_captureSource === captureSource.CODE) {
      type = ERROR_CODES_PROMETHEUS.SHIELD_API_CODE;
    } else if (isKnownApiError) {
      type = ERROR_CODES_PROMETHEUS.SHIELD_API_KNOWN;
    } else {
      type = ERROR_CODES_PROMETHEUS.SHIELD_API_UNKNOWN;
    }
  }

  trackEvents.prometheus({ type, label: flow, errorDescription, isShieldEvent: true });
};

const getError = (error, isApiError) => {
  if (isApiError && error?.actualError) {
    return error.actualError;
  }
  return typeof error === 'object' ? error.message || 'unknown' : error;
};

const isKnownErrorAPI = (error) => {
  console.log('[@isKnownErrorAPI]', error);

  try {
    const MESSAGES_IGNORE = [
      ...IGNORE_MESSAGES,
      ...Object.keys(authApiResponse || {}).map((val) => authApiResponse[val]),
      ...twoFaErrors,
    ];
    if (error?.isApiError && error?.actualError) {
      if (error?.flow === COMMON_ENDPOINTS.USER) {
        return error.actualError
          .toLowerCase()
          .includes(IGNORE_MESSAGE_ONLY_FOR_USER_API.toLowerCase());
      } else if (
        MESSAGES_IGNORE.some((msg) => error.actualError.toLowerCase().includes(msg.toLowerCase()))
      ) {
        return true;
      }
    }
  } catch (e) {
    console.log('[@isKnownErrorAPI] - catch', e);
    // do nothing as funtction will return false in such case
  }
  return false;
};

// error can be error object or an actual string description
const captureException = (error, additionalData = {}) => {
  const flow = additionalData.flow || 'unknown';
  const _captureSource = additionalData.captureSource || captureSource.CODE;
  const isApiError = !!error?.isApiError;
  const IS_KNOWN_ERROR_API = isKnownErrorAPI(error);

  console.log('[@captureException]', error, additionalData);

  _logErrorToPrometheus(
    getError(error, isApiError),
    flow,
    isApiError,
    IS_KNOWN_ERROR_API,
    _captureSource,
  );

  if (!IS_KNOWN_ERROR_API) {
    errorService.captureError(error, {
      tags: {
        ...additionalData,
        errorMessage: error?.message || '',
        flow,
      },
    });
  }
};

export const captureSignupException = (error) => {
  console.log('[@captureSignupException]', error);

  captureException(error, {
    flow: sentryFlows.SIGNUP,
  });
};

export const captureSigninException = (error) => {
  console.log('[@captureSigninException]', error);
  captureException(error, {
    flow: sentryFlows.SIGNIN,
  });
};

export default captureException;
