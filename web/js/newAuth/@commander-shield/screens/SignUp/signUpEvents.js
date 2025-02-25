import trackEvents, { GTAG_KEYS, DESKTOP_SCREEN_PX } from '../../js/analytics';
import { getCaptchaVariant } from '../../utils/captchaService';
import getIsLandingPageUser from '../../utils/getIsLandingPageUser';
import getSessionInfo from '../../utils/getSessionInfo';
import { getAttribUtmData } from '../../utils/getUtmData';
import getExperimentIds from '../../utils/getExperimentIds';
import { getReferralParams } from '../../utils/referralParams';
import { authMethods } from '../screenHelpers';

const utmData = getAttribUtmData();

const signUpEvents = {
  trackSignUpInitiate: (user, method = authMethods.EMAIL, gAuthType = 'none') => {
    trackEvents.prometheus({ type: 'signup', label: 'create_account_initiated' });

    const referralParams = getReferralParams();
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'create_account',
      data: {
        emailId: user.email,
        method,
        googleAuthVariant: gAuthType,
        captcha_variant: getCaptchaVariant(),
      },
      toCleverTap: true,
    });

    trackEvents.social({
      fb: 'signup_start',
      quora: 'GenerateLead',
      reddit: 'Lead',
      linkedIn: '987388',
      twitter: 'o1u9x',
    });

    trackEvents.gtag(GTAG_KEYS.signupStart);

    if (user.partnerIntent) {
      trackEvents.social({
        fb: 'partner_signup_start',
        linkedIn: '1668332',
      });

      trackEvents.ga(
        'Partner Onboarding',
        'Email and Password',
        'Partner Onboarding | Click Create Account',
      );
    }

    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'create account cta clicked',
      screen: 'home page',
    });

    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Create Account Initiated',
      screen: 'home page',
      properties: {
        referralCode: referralParams.referralCode,
      },
    });
  },

  trackPageLoad: (user, props) => {
    const sessionInfo = getSessionInfo();
    const referralParams = getReferralParams();

    trackEvents.prometheus({ type: 'signup', label: 'signup_landed' });

    trackEvents.dataLake({
      type: 'success',
      eventName: 'display_signup_page',
      data: {
        emailId: user.email,
        first_utm: utmData.firstUtm,
        last_utm: utmData.lastUtm,
        ref_url: user.referrer,
        service: user.serviceName,
        first_page: utmData.firstPage,
        final_page: utmData.finalPage,
        referring_url: utmData.website,
        website: utmData.website,
        coupon: user?.coupon?.code,
        is_landing_page_user: getIsLandingPageUser(),
        is_landing_page_session: sessionInfo.isLandingPageSession,
        common_session_id: sessionInfo.commonSessionId,
        captcha_variant: getCaptchaVariant(),
        ...props,
      },
    });

    trackEvents.dataLake({
      type: 'success',
      eventName: 'displayed',
      data: {
        emailId: user.email,
        first_utm: utmData.firstUtm,
        last_utm: utmData.lastUtm,
        ref_url: user.referrer,
        service: user.serviceName,
        first_page: utmData.firstPage,
        final_page: utmData.finalPage,
        referring_url: utmData.website,
        website: utmData.website,
        coupon: user?.coupon?.code,
        is_landing_page_user: getIsLandingPageUser(),
        is_landing_page_session: sessionInfo.isLandingPageSession,
        common_session_id: sessionInfo.commonSessionId,
        captcha_variant: getCaptchaVariant(),
        ...props,
      },
      toCleverTap: false,
    });

    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Displayed',
      screen: 'home page',
      toCleverTap: false,
    });

    if (referralParams.referralCode) {
      trackEvents.segment({
        objectName: 'Referral Sign Up Page',
        actionName: 'Viewed',
        screen: 'home page',
      });
    }

    trackEvents.ga('Signup - Email Password', 'Open - Signup Page');
    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'page visit',
      screen: 'home page',
    });
    trackEvents.criteo({ type: 'view' });
  },

  // track back for all screens
  trackBack: (user, screen) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'back_action',
      data: {
        mid: user.mid,
        userid: user.id,
        source: screen,
      },
    });

    trackEvents.ga('Signup - Steps', 'Click - Back', screen);
  },

  trackSecondaryLinkClick: ({ user, source }) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'secondary_links',
      data: {
        emailId: user.email,
        source,
      },
    });

    trackEvents.ga('Signup - Email Password', `Click - ${source}`);
  },

  trackCaptchaSuccess: (user, method = authMethods.EMAIL) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'captcha_verification',
      data: {
        emailId: user.email,
        captcha_verified_by: user.captchaMode,
        captcha_variant: getCaptchaVariant(),
        called_on_v3_failure: user.calledOnV3Failure || false,
        method,
      },
    });

    trackEvents.ga('Signup - Email Password', 'Captcha Success');
  },

  trackCaptchaFailure: ({ email, error, captchaMode, method }) => {
    if (captchaMode === 'v3') {
      trackEvents.dataLake({
        type: 'failed',
        eventName: 'captchav3_verification',
        data: {
          emailId: email,
          error,
          captcha_variant: getCaptchaVariant(),
          method,
        },
      });
    }
  },

  // pass our backend userid and mid(as property) to segment so that it can mapped to own userId property.
  // pass Experiment_ID ( which will have razorx and optimise enabled experiments ) to segment
  // pass signup source in the user property
  bindSegmentUserIdentity: (user, locationQuery) => {
    const experimentIds = getExperimentIds(user, locationQuery);
    const isMobile = window.innerWidth < DESKTOP_SCREEN_PX;
    trackEvents.segmentIdentify(user.id, {
      mid: user.mid,
      Experiment_ID: experimentIds.length ? experimentIds : 'none',
      signup_source: isMobile ? 'Mobile DWeb' : 'DWeb',
    });
  },

  trackRedirectToActivationForm: (user) => {
    trackEvents.segmentIdentify(user.id, {
      loginL1Experiment: 'redirect to activation page',
      mid: user.mid,
      screen: 'sign up',
    });
  },

  trackSignUpSuccess: (user, method = authMethods.EMAIL, isPartner, gAuthType = 'none') => {
    const referralParams = getReferralParams();
    trackEvents.prometheus({ type: 'signup', label: 'create_account_success' });

    trackEvents.dataLake({
      type: 'success',
      eventName: 'create_account',
      data: {
        emailId: user.email,
        mid: user.mid,
        userid: user.id,
        method,
        googleAuthVariant: gAuthType,
        contact: user.contact,
      },
      toCleverTap: true,
    });

    trackEvents.ga('set', '&uid', btoa(user.email));

    trackEvents.ga('Signup - Email Password', 'Click - Create Account (Success)');

    trackEvents.hubspot({
      id: 'SIGNUP_COMPLETE',
    });

    trackEvents.hubspot({
      name: 'create_contact',
      data: {
        email: user.email,
        isPartner,
      },
    });

    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'create account success',
      screen: 'home page',
    });

    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Create Account Result',
      screen: 'home page',
      properties: {
        status: 'success',
        referralCode: referralParams?.referralCode,
        type: method,
        merchantId: user?.mid,
      },
      toCleverTap: true,
    });

    // https://razorpay.slack.com/archives/C0BULSEUS/p1639131463483100
    trackEvents.gtag(GTAG_KEYS.marketingCreateAccountSuccess, { allow_custom_scripts: true });
  },

  trackSignUpError: (
    { error, email, apiResponseTime, statusCode, actualError },
    method = authMethods.EMAIL,
    gAuthType = 'none',
  ) => {
    const referralParams = getReferralParams();
    trackEvents.dataLake({
      type: 'failed',
      eventName: 'create_account',
      data: {
        emailId: email,
        error,
        method,
        googleAuthVariant: gAuthType,
        apiResponseTime, // add to debug response time of apis in case of default error
        statusCode,
        actualError,
      },
      toCleverTap: true,
    });

    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'Create Account Result',
      screen: 'home page',
      properties: {
        status: 'failure',
        referralCode: referralParams?.referralCode,
        error,
        type: method,
      },
    });

    trackEvents.hubspot({ id: 'SIGNUP_FAILED', value: error });

    trackEvents.ga('Signup - Email Password', 'Click - Create Account (Error)', error);
  },

  trackNativeAuthInitiate: (user, { email, method = authMethods.EMAIL }) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'native_auth',
      data: {
        emailId: email,
        service: user.serviceName,
        captcha_variant: getCaptchaVariant(),
        method,
      },
      toCleverTap: true,
    });

    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'form cta clicked',
      screen: 'home page',
    });
    trackEvents.segment({
      objectName: 'Sign Up',
      actionName: 'CTA Clicked',
      screen: 'home page',
    });
  },

  // comes into picture when experiment for onetap is ON.
  // Fires after type of gAuthType(g-btn/g-one-tap) to be shown is decided.
  trackGoogleAuthVariantDecide: (user, gAuthType, oneTapHideReason) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'display_google_auth',
      data: {
        googleAuthVariant: gAuthType,
        service: user.serviceName,
        oneTapHide: oneTapHideReason,
        emailId: user.email,
        first_utm: utmData.firstUtm,
        last_utm: utmData.lastUtm,
        ref_url: user.referrer,
        first_page: utmData.firstPage,
        final_page: utmData.finalPage,
        website: utmData.website,
      },
    });
  },

  trackGoogleAuthOneTapDismissed: (user, oneTapDismissReason) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'google_onetap_dismissed',
      data: {
        service: user.serviceName,
        dismissedReason: oneTapDismissReason,
      },
    });
  },

  trackOneTapClose: (user) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'google_onetap_close',
      data: {
        service: user.serviceName,
      },
    });
  },

  trackGoogleAuthInitiate: (user, gAuthType) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'google_oauth',
      data: {
        service: user.serviceName,
        googleAuthVariant: gAuthType,
      },
      toCleverTap: true,
    });
    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'with google cta clicked',
      screen: 'home page',
    });
  },

  trackGoogleAuthSuccess: (user, gAuthType) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'google_oauth',
      data: {
        service: user.serviceName,
        emailId: user.email,
        googleAuthVariant: gAuthType,
      },
      toCleverTap: true,
    });
  },

  trackGoogleAuthFail: (user, error, gAuthType) => {
    trackEvents.dataLake({
      type: 'failed',
      eventName: 'google_oauth',
      data: {
        service: user.serviceName,
        googleAuthVariant: gAuthType,
        error,
      },
      toCleverTap: true,
    });
  },

  trackAttachClickHandler: (user, googleAuthInstance, element, isClicked, gAuthType) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'google_oauth_click_handler',
      data: {
        service: user.serviceName,
        googleAuthInstance,
        element,
        isClicked,
        gAuthType,
      },
    });
  },

  trackLoginSuccess: (user, { mid, id, email }) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'login.login',
      data: {
        service: user.serviceName,
        emailId: email,
        mId: mid,
        userId: id,
      },
    });
  },
  trackEmailInput: () => {
    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'email filled',
      screen: 'home page',
    });
  },
  trackPasswordInput: () => {
    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'password filled',
      screen: 'home page',
    });
  },

  trackInputError: (error, field) => {
    trackEvents.segment({
      objectName: 'Form Field',
      actionName: 'Validation Failed',
      screen: 'home page',
      properties: {
        error,
        fieldLabel: field,
        tab: 'Sign Up',
      },
    });
  },

  trackSignUpMethodChangeInitiate: (method) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'identifier_change',
      data: {
        method,
      },
    });
  },

  trackResendOtpInitiate: () => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'resend_otp',
      data: {
        method: authMethods.PHONE_NUMBER,
      },
    });
  },

  trackResendOtpSuccess: () => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'resend_otp',
      data: {
        method: authMethods.PHONE_NUMBER,
      },
    });
  },

  trackResendOtpFailure: () => {
    trackEvents.dataLake({
      type: 'failed',
      eventName: 'resend_otp',
      data: {
        method: authMethods.PHONE_NUMBER,
      },
    });
  },
};
export default signUpEvents;
