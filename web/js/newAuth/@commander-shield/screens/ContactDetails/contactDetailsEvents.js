import findIndex from '@razorpay/universe-utils/findIndex';
import trackEvents, { GTAG_KEYS } from '../../js/analytics';
import { authMethods } from '../screenHelpers';

const contactDetailsEvents = {
  trackCouponCodeInitiate: (user, coupon) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'apply_coupon_code',
      data: {
        mid: user.mid,
        userid: user.id,
        source: coupon,
      },
      toCleverTap: true,
    });
    trackEvents.ga('Signup - Steps', 'Click - Coupon Apply', coupon);
  },

  trackCouponCodeSuccess: (user, coupon) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'apply_coupon_code',
      data: {
        mid: user.mid,
        userid: user.id,
        source: coupon,
      },
      toCleverTap: true,
    });
    trackEvents.ga('Signup - Steps', 'Coupon - Response', `Success | ${coupon}`);
  },

  trackCouponCodeError: (coupon, error) => {
    trackEvents.ga('Signup - Steps', 'Coupon - Response', `Fail | ${coupon} | ${error}`);
  },

  trackGotCouponCodeClick: (user) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'display_coupon_field',
      data: {
        mid: user.mid,
        userid: user.id,
      },
    });
    trackEvents.ga('Signup - Steps', 'Click- Got a coupon code');
  },

  trackInputError: (error, field) => {
    trackEvents.segment({
      objectName: 'Form Field',
      actionName: 'Validation Failed',
      screen: 'home page',
      properties: {
        error,
        fieldLabel: field,
        tab: 'Contact Details',
      },
    });
  },

  trackSubmitInitiate: (user, method = authMethods.EMAIL) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'finish_signup',
      data: {
        mid: user.mid,
        userid: user.id,
        method,
      },
      toCleverTap: true,
    });
    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'contact details next cta clicked',
      screen: 'contact page',
      toCleverTap: true,
    });
  },

  trackSubmitSuccess: (user, method = authMethods.EMAIL) => {
    trackEvents.prometheus({ type: 'signup', label: 'pre_signup_success' });

    const businessType = user.businessDetails.type;

    const businessTypeName =
      findIndex(user?.businessTypes?.registered, (item) => item.id === businessType) !== -1
        ? 'Registered'
        : 'Unregistered';

    trackEvents.ga('Signup - Steps', 'Click - Finish', businessTypeName);

    trackEvents.social({
      quora: 'CompleteRegistration',
      reddit: 'SignUp',
      fb: 'signup_complete',
    });

    trackEvents.dataLake({
      type: 'success',
      eventName: 'finish_signup',
      data: {
        mid: user.mid,
        userid: user.id,
        emailId: user.email,
        whatsAppOptIn: user.isWhatsAppOptIn,
        method,
        'MSG-whatsapp': user.isWhatsAppOptIn, // This key is for clevertap. Data lake events are being send to segment and from segment to clevertap
      },
      toCleverTap: true,
    });

    trackEvents.ga('sign-up-form-success');
    trackEvents.ga('sign-up-form-success');
    trackEvents.gtag(GTAG_KEYS.defaultSignupComplete);
    trackEvents.gtag(GTAG_KEYS.marketingSignupComplete, { allow_custom_scripts: true });
    trackEvents.bing();
    trackEvents.social({
      twitter: 'o1tr7',
      twitterAgency: 'o4ux5',
      linkedIn: '391804',
    });
    trackEvents.hubspot({
      name: 'update_property',
      data: {
        email: user.email,
        signup_business_type: user.businessDetails.type,
        signup_transaction_volume: user.businessDetails.monthlyRevenue,
        signup_contact_mobile: user.contact,
        signup_contact_name: user.name,
      },
    });

    if (user.partnerIntent) {
      const fbEventSuffix = businessType === 'unregistered' ? 'unreg' : 'reg';
      trackEvents.social({
        fb: `partner_signup_complete_${fbEventSuffix}`,
        linkedIn: '1668316',
      });
      trackEvents.ga(
        'Partner Onboarding',
        'Contact Details',
        `Partner Onboarding | Fill & Finish | ${businessTypeName}`,
      );
    }

    if (businessType === 'unregistered') {
      trackEvents.social({ fb: 'signup_complete_unreg' });
      trackEvents.gtag(GTAG_KEYS.signupCompleteUnreg);
    } else {
      trackEvents.social({ fb: 'signup_complete_reg' });
      trackEvents.gtag(GTAG_KEYS.signupCompleteReg);
    }

    trackEvents.criteo({ type: 'signup_complete', email: user.email });
  },

  trackSubmitError: ({ user, error, method }) => {
    trackEvents.ga('Signup - Steps', 'Click - Finish', error);
    trackEvents.dataLake({
      type: 'failed',
      eventName: 'finish_signup',
      data: {
        mid: user.mid,
        userid: user.id,
        emailId: user.email,
        error,
        method,
      },
      toCleverTap: true,
    });
  },
};

export default contactDetailsEvents;
