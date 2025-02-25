/* eslint-disable max-statements */
import { captureSignupException } from '../../../shared/captureException';
import { signUpHeaders, signUpSrc, authModes, authMethods } from '../../../screens/screenHelpers';
import {
  VERSION_ONE_TAP_ENABLED,
  VERSION_MOBILE_SIGNUP,
  setSignUpAnalyticsVersion,
  setSignUpSourceAnalytics,
  VERSION_WEBSITE_HOMEPAGE,
  setSignupCTASource,
} from '../../../js/analytics';
import { userInitialState } from '../../../user/userDuck';

export const signInUrlHelpers = (locationQuery, actions) => {
  const redirectUrl = locationQuery.get('next') || ''; // next = url to open upon login

  if (redirectUrl) {
    const parser = document.createElement('a');
    parser.href = redirectUrl;
    const hostname = parser.hostname || location.hostname;

    // eslint-disable-next-line
    if (/razorpay\.(com|dev|in)$/.test(hostname) && parser.protocol !== 'javascript:') {
      actions.updateUser({
        redirectUrl: parser.href,
      });
    }
  }
};

// eslint-disable-next-line complexity
const urlHelpers = async (locationQuery, actions, showMobileSignup, defaultCoupon, orgName) => {
  try {
    const email = locationQuery.get('email');
    const invitationCode = locationQuery.get('invitation');
    const merchantInvitationCode = locationQuery.get('merchant_invitation');
    const partner = locationQuery.get('r') === 'partner';
    const referralCode = locationQuery.get('referral_code');
    const ref = locationQuery.get('ref');
    const referrer = locationQuery.get('utm_source');
    const couponCode = locationQuery.get('coupon_code');
    const merchant = locationQuery.get('merchant') || '';
    const authSource = locationQuery.get('auth_source') || '';
    const signupCTASource = locationQuery.get('signup_cta_source') || '';
    const isEmailOtpSignupEnabled = locationQuery.get('email_otp') === 'true';
    let signupAnalyticsVersion = VERSION_ONE_TAP_ENABLED;
    let isInvited = !!referralCode;

    const merchantX = 'x'; // ?merchant=x
    const serviceNames = {
      PG: 'PG',
      X: 'X',
      PARTNER: 'Partner',
      OTHER: 'Other',
    };
    const stateObject = {
      ...userInitialState,
      authMode: authModes.GAUTH,
      authMethod: authMethods.GAUTH,
    };

    // setting version for analytics
    if (showMobileSignup) {
      signupAnalyticsVersion = VERSION_MOBILE_SIGNUP;
    } else if (authSource && authSource === signUpSrc.websiteHomePage) {
      signupAnalyticsVersion = VERSION_WEBSITE_HOMEPAGE;
    }
    setSignUpAnalyticsVersion(signupAnalyticsVersion);

    if (invitationCode) {
      try {
        const resp = await actions.verifyInvitationCode(invitationCode);
        stateObject.user.email = resp.user.email;
        stateObject.authMethod = authMethods.EMAIL;
        stateObject.authMode = authModes.PASSWORD;
        stateObject.user.invitationCode = invitationCode;
        isInvited = true;
      } catch (error) {
        captureSignupException(error);
      }
    }

    if (merchantInvitationCode && orgName) {
      try {
        const resp = await actions.verifyMerchantInvitationCode(merchantInvitationCode, orgName);
        stateObject.user.email = resp.user.email;
        stateObject.authMethod = authMethods.EMAIL;
        stateObject.authMode = authModes.PASSWORD;
        stateObject.user.merchantInvitationCode = merchantInvitationCode;
        isInvited = true;
      } catch (error) {
        captureSignupException(error);
      }
    }

    if (merchant === merchantX) {
      stateObject.user.serviceName = serviceNames.X;
    } else if (partner) {
      stateObject.user.serviceName = serviceNames.PARTNER;
    } else if (isInvited) {
      stateObject.user.serviceName = serviceNames.OTHER;
    } else {
      stateObject.user.serviceName = serviceNames.PG;
    }

    if (email) {
      stateObject.user.email = atob(decodeURIComponent(email));
    }

    if (partner) {
      stateObject.user.partnerIntent = true;
    }

    if (authSource) {
      if (authSource === signUpSrc.websitePaymentLink) {
        setSignUpSourceAnalytics(signUpSrc.websitePaymentLink);
        actions.updateSignUpSource(signUpSrc.websitePaymentLink);
        actions.updateSignUpHeading(signUpHeaders.paymentLink);
      } else if (authSource === signUpSrc.websiteHomePage) {
        setSignUpSourceAnalytics(signUpSrc.websiteHomePage);
        actions.updateSignUpSource(signUpSrc.websiteHomePage);
      }
    }

    if (signupCTASource) {
      setSignupCTASource(signupCTASource);
    }

    if (referralCode) {
      stateObject.user.referralCode = referralCode;
    }

    if (ref) {
      stateObject.user.ref = ref;
    }

    if (referrer) {
      stateObject.user.referrer = referrer;
    }

    stateObject.isMobileNumberSignupEnabled = showMobileSignup;
    stateObject.isEmailOtpSignupEnabled = isEmailOtpSignupEnabled;

    const campaignStartDate = new Date('January 19, 2021 00:00:01').getTime();
    const campaignEndDate = new Date('May 15, 2021 23:59:00').getTime();

    // coupons priority
    // 1. apply the one in the URL
    // 2. apply unlock2021 till May 15th

    if (defaultCoupon) {
      stateObject.user.coupon = {
        code: defaultCoupon,
      };
    } else if (couponCode) {
      stateObject.user.coupon = {
        code: couponCode,
      };
    } else if (new Date().getTime() > campaignStartDate && new Date().getTime() < campaignEndDate) {
      stateObject.user.coupon = {
        code: 'UNLOCK2021',
      };
    }

    actions.updateState(stateObject);
  } catch (errorSignupUrlHelper) {
    captureSignupException(errorSignupUrlHelper);
  }
};

export default urlHelpers;
