// @ts-check
import * as signupApiActions from '../api/signupApi';
import * as apiActions from '../api/commonApi';
import { getSignUpHeading, authModes, authMethods } from '../screens/screenHelpers';
import * as signinApiActions from '../api/signinApi';

const UPDATE_ORG = 'UPDATE_ORG';
const UPDATE_USER = 'UPDATE_USER';
const UPDATE_STATE = 'UPDATE_STATE';
const UPDATE_USER_BUSINESS_DETAILS = 'UPDATE_USER_BUSINESS_DETAILS';
const UPDATE_LOADER = 'UPDATE_LOADER';
const UPDATE_COUPON_CODE = 'UPDATE_COUPON_CODE';
const UPDATE_ERROR_STATE = 'UPDATE_ERROR_STATE';
const UPDATE_SIGNUP_SOURCE = 'UPDATE_SIGNUP_SOURCE';
const UPDATE_SIGNUP_HEADING = 'UPDATE_SIGNUP_HEADING';
const UPDATE_AUTH_METHOD_AUTH_MODE = 'UPDATE_AUTH_METHOD_AUTH_MODE';
const GET_BUSINESS_TYPES = 'GET_BUSINESS_TYPES';
const UPDATE_LOGIN_DETAILS = 'UPDATE_LOGIN_DETAILS';
const UPDATE_TNC_POPUP = 'UPDATE_TNC_POPUP';
const LOGOUT_USER = 'LOGOUT_USER';

const actionTypes = {
  UPDATE_ORG,
  UPDATE_USER,
  UPDATE_STATE,
  UPDATE_USER_BUSINESS_DETAILS,
  UPDATE_LOADER,
  UPDATE_COUPON_CODE,
  UPDATE_ERROR_STATE,
  UPDATE_SIGNUP_SOURCE,
  UPDATE_SIGNUP_HEADING,
  UPDATE_AUTH_METHOD_AUTH_MODE,
  GET_BUSINESS_TYPES,
  UPDATE_LOGIN_DETAILS,
  UPDATE_TNC_POPUP,
  LOGOUT_USER,
};

export const userInitialState = {
  org: {},
  isLoading: false,
  hasError: false,
  signUpSource: 'dashboard',
  signUpHeading: getSignUpHeading(),
  isMobileNumberSignupEnabled: false,
  isEmailOtpSignupEnabled: false,
  authMode: '',
  authMethod: '',
  signInGoogleAuthType: '',
  user: {
    mid: '',
    email: undefined,
    password: '',
    mobileNumber: '',
    otpToken: '',
    isMobileNumberVerified: false,
    isOwner: false,
    id: '',
    name: '',
    contact: undefined,
    referralCode: '',
    partnerIntent: false,
    serviceName: '',
    invitationCode: undefined,
    merchantInvitationCode: undefined,
    token: '',
    ref: '',
    censoredEmail: '',
    businessDetails: {
      type: '',
      monthlyRevenue: '',
    },
    coupon: {
      code: '',
      credit: '',
      expiry: '',
    },
    previousValidatedCoupon: {
      code: '',
      credit: '',
      expiry: '',
    },
    isWhatsAppOptIn: true,
    redirectUrl: '', // initialized from query param "?next=app/dashboard",
    isSignupViaEmail: '',
    loggedInVia: '',
    businessTypes: {},
  },
  showTncPopup: false,
  loginDetails: {},
};

/**
 * @typedef {typeof userInitialState} State
 */

/**
 * @typedef {ReturnType<userActions>} Actions
 */

/**
 * @typedef {keyof typeof actionTypes} ActionTypes
 */

/**
 * @template T
 * @typedef {T extends T ? { type: T, data: Record<string, any> } : never} ActionCreator
 */

/**
 * @param {State} state
 * @param {ActionCreator<ActionTypes>} action
 * @returns {State}
 */
export const userReducer = (state, action) => {
  switch (action.type) {
    case actionTypes.UPDATE_ORG: {
      return {
        ...state,
        org: {
          ...state.org,
          ...action.data,
        },
      };
    }

    case actionTypes.UPDATE_USER: {
      return {
        ...state,
        user: {
          ...state.user,
          ...action.data,
        },
      };
    }

    case actionTypes.UPDATE_STATE: {
      return {
        ...state,
        ...action.data,
      };
    }

    case actionTypes.UPDATE_USER_BUSINESS_DETAILS: {
      return {
        ...state,
        user: {
          ...state.user,
          businessDetails: {
            ...state.user.businessDetails,
            ...action.data,
          },
        },
      };
    }

    case actionTypes.UPDATE_LOADER: {
      return {
        ...state,
        isLoading: action.data.isLoading,
      };
    }

    case actionTypes.UPDATE_ERROR_STATE: {
      return {
        ...state,
        hasError: action.data.hasError,
      };
    }

    case actionTypes.UPDATE_COUPON_CODE: {
      return {
        ...state,
        user: {
          ...state.user,
          coupon: {
            ...state.user.coupon,
            ...action.data,
          },
        },
      };
    }

    case actionTypes.UPDATE_SIGNUP_SOURCE: {
      return {
        ...state,
        signUpSource: action.data.signUpSource,
      };
    }

    case actionTypes.UPDATE_SIGNUP_HEADING: {
      return {
        ...state,
        signUpHeading: action.data.signUpHeading,
      };
    }

    case actionTypes.UPDATE_AUTH_METHOD_AUTH_MODE: {
      return {
        ...state,
        authMethod: action.data.authMethod,
        authMode: action.data.authMode,
      };
    }

    case actionTypes.GET_BUSINESS_TYPES: {
      return {
        ...state,
        user: {
          ...state.user,
          businessTypes: action.data,
        },
      };
    }

    case actionTypes.UPDATE_LOGIN_DETAILS: {
      return {
        ...state,
        loginDetails: {
          ...state.loginDetails,
          ...action.data,
        },
      };
    }

    case actionTypes.UPDATE_TNC_POPUP: {
      return {
        ...state,
        showTncPopup: action.data.showTncPopup,
      };
    }

    default: {
      return state;
    }
  }
};

/**
 * @param {State} state
 * @param {React.Dispatch<{type: ActionTypes, data: any}>} dispatch
 */
export const userActions = (state, dispatch) => ({
  updateUser: (user) => {
    dispatch({ type: UPDATE_USER, data: user });
  },

  updateState: (data) => {
    dispatch({ type: UPDATE_STATE, data });
  },

  updateUserBusinessDetails: (businessDetails) => {
    dispatch({ type: UPDATE_USER_BUSINESS_DETAILS, data: businessDetails });
  },

  showLoader: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
  },

  hideLoader: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
  },

  getOrgDetails: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await apiActions.getOrgDetails();
        dispatch({ type: UPDATE_ORG, data: res.data.org });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  getUserDetails: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await apiActions.getUserDetails();
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  registerUser: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.registerUser(user);
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      }
    });
  },

  sendSignUpOTP: ({ contact, email }) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.sendRegisterOTP({ contact, email });
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  resendSignUpOTP: ({ contact, token, email }) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.resendSignupOTP({ contact, token, email });
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  verifySignUpOTP: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.verifyOTP(user);
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },
  getActiveBusinessTypes: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.getActiveBusinessTypes();
        dispatch({ type: GET_BUSINESS_TYPES, data: res.data });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },
  sendDeviceDetails: (mobileNumberFlow = false) => {
    if (!mobileNumberFlow) {
      dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    }
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.sendDeviceDetails();
        resolve(res.data);
      } catch (e) {
        /* This check is required as devicedetails api is made without using await in mobile flow and 
            here loader state is globally maintained. So, it can make another api call isLoading: false
            if response comes beofre another api call response.
        */
        if (!mobileNumberFlow) {
          dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        }
        reject(e.error);
      }
    });
  },
  sendPreSignUpData: (businessDetails, { email, isPreSignUpEnabled = true }) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.sendPresignupData(
          {
            ...state.user.businessDetails,
            ...businessDetails,
            couponCode: state.user.previousValidatedCoupon.code,
            isWhatsAppOptIn: state.user.isWhatsAppOptIn,
            referralCode: state.user.referralCode,
            email,
          },
          isPreSignUpEnabled,
        );
        dispatch({ type: UPDATE_USER_BUSINESS_DETAILS, data: businessDetails });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      }
    });
  },

  verifyEmail: (otp) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.verifyUserEmail({
          token: state.user.token,
          otp,
        });
        resolve(res.data);
      } catch (e) {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
        reject(e.error);
      }
    });
  },

  resendOtp: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.resendOtp({
          token: state.user.token,
        });
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  verifyInvitationCode: (invitationCode) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.verifyInvitationCode(invitationCode);
        dispatch({ type: UPDATE_USER, data: { ...res.data.user, invitationCode } });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  verifyMerchantInvitationCode: (merchantInvitationCode, orgName) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.verifyMerchantInvitationCode(
          merchantInvitationCode,
          orgName,
        );
        dispatch({ type: UPDATE_USER, data: { ...res.data.user, merchantInvitationCode } });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  validateCouponCode: (couponCode) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signupApiActions.validateCouponCode(couponCode);
        dispatch({ type: UPDATE_COUPON_CODE, data: { code: couponCode, ...res.data.user.coupon } });
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  showErrorScreen: () => {
    dispatch({ type: UPDATE_ERROR_STATE, data: { hasError: true } });
  },

  updateSignUpSource: (source) => {
    dispatch({ type: UPDATE_SIGNUP_SOURCE, data: { signUpSource: source } });
  },

  updateSignUpHeading: (heading) => {
    dispatch({ type: UPDATE_SIGNUP_HEADING, data: { signUpHeading: heading } });
  },

  registerUserWithGoogle: (email, googleToken, signupCampaign) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await apiActions.registerUserWithGoogle({
          email,
          googleToken,
          partnerIntent: state.user.partnerIntent,
          signupCampaign,
        });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  loginWithGoogle: (email, googleToken, route) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await apiActions.loginWithGoogle({
          email,
          googleToken,
          route,
        });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  // login apis. Please check if these are reqd. here.
  login: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.loginUser({
          email: user.email,
          password: user.password,
          captcha: user.captcha,
          captchaMode: user.captchaMode,
        });
        dispatch({ type: UPDATE_LOGIN_DETAILS, data: res.data });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  sendLoginOTP: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    let payload;
    if (user.mobileNumber) {
      payload = { mobileNumber: user.mobileNumber };
    } else {
      payload = { email: user.email };
    }
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.sendOTP(payload);
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  userVerificationSendOTP: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.userVerificationSendOTP({
          contact: user.contact,
          password: user.password,
        });
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  /** @param {{mobileNumber?: string, email?: string, otpToken: string}} user */
  resendLoginOTP: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    let payload;
    if (user.mobileNumber) {
      payload = { mobileNumber: user.mobileNumber, otpToken: user.otpToken };
    } else {
      payload = { email: user.email, otpToken: user.otpToken };
    }

    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.resendOTP(payload);
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  userVerificationResendOTP: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.userVerificationResendOTP({
          mobileNumber: user.mobileNumber,
          password: user.password,
          otpToken: user.otpToken,
        });
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  /**
   *
   * @param {{
   * mobileNumber?: string;
   * email?: string;
   * isMobileNumberVerified?: boolean;
   * otp: number;
   * otpToken: string;
   * captcha: string;
   * captchaMode: 'v3' | 'invisible';
   * }} user
   * @returns
   */
  loginWithOTP: (user) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        let res;
        const commonPayload = {
          mobileNumber: user.mobileNumber,
          otp: user.otp,
          otpToken: user.otpToken,
          captcha: user.captcha,
          captchaMode: user.captchaMode,
        };

        if (user.mobileNumber) {
          const mobilePayload = {
            mobileNumber: user.mobileNumber,
            ...commonPayload,
          };
          if (user.isMobileNumberVerified) {
            res = await signinApiActions.verifyOTP(mobilePayload);
          } else {
            res = await signinApiActions.userVerificationVerifyOTP(mobilePayload);
          }
        } else {
          const emailPayload = {
            email: user.email,
            ...commonPayload,
          };
          res = await signinApiActions.verifyOTP(emailPayload);
        }
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  verify2faOtp: (otp) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.verify2faOTP({
          otp,
        });
        // set user details(loggedInVia) & use after accepting Terms & Conditions
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  resend2faOtp: () => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.resend2faOTP();
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  verify2FAPassword: (password) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.verify2FAPassword({
          password,
        });
        // set user details(loggedInVia) & use after accepting Terms & Conditions
        dispatch({ type: UPDATE_USER, data: res.data.user });
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  resetPassword: (email) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.resetPassword({
          email,
        });
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  resetPasswordToken: (user) => {
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.resetPasswordToken({
          email: user.email,
          password: user.password,
          passwordConfirmation: user.passwordConfirmation,
          token: user.token,
        });
        resolve(res);
      } catch (e) {
        reject(e.error);
      }
    });
  },

  emailUpdate: (user) => {
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.emailUpdate({
          password: user.password,
          passwordConfirmation: user.passwordConfirmation,
          token: user.token,
          merchant_id: user.merchant_id,
        });
        resolve(res);
      } catch (e) {
        reject(e.error);
      }
    });
  },

  setup2faMobileNumber: (mobileNumber) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.setup2faMobileNumber({
          mobileNumber,
        });
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  setAuthMethodAndAuthMode: (data) => {
    dispatch({ type: UPDATE_AUTH_METHOD_AUTH_MODE, data });
  },

  resetAuthMethodAndAuthMode: () => {
    dispatch({
      type: UPDATE_AUTH_METHOD_AUTH_MODE,
      data: { authMode: authModes.GAUTH, authMethod: authMethods.GAUTH },
    });
  },

  updateTermsAndConditions: (status = false) => {
    dispatch({ type: UPDATE_LOADER, data: { isLoading: true } });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await apiActions.updateTermsAndConditions(status);
        resolve(res);
      } catch (e) {
        reject(e.error);
      } finally {
        dispatch({ type: UPDATE_LOADER, data: { isLoading: false } });
      }
    });
  },

  updateLoginDetails: (data) => {
    dispatch({ type: UPDATE_LOGIN_DETAILS, data });
  },

  showTncPopup: () => {
    dispatch({ type: UPDATE_TNC_POPUP, data: { showTncPopup: true } });
  },

  hideTncPopup: () => {
    dispatch({ type: UPDATE_TNC_POPUP, data: { showTncPopup: false } });
  },

  logout: () => {
    dispatch({ type: LOGOUT_USER, data: {} });
    return new Promise(async (resolve, reject) => {
      try {
        const res = await signinApiActions.logoutUser();
        resolve(res.data);
      } catch (e) {
        reject(e.error);
      }
    });
  },
});
