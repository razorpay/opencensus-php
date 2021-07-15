import { getMode } from 'v2/services/mode';
/* Delimiters are space / underscore */
export const titleCase = (sentence) => {
  return (sentence || '')
    .split(/\s+|_/)
    .map((word) => word.charAt(0).toUpperCase() + word.substr(1).toLowerCase())
    .join(' ');
};

const getCommonProperties = ({ screen, properties, user }) => {
  const eventTimestamp = new Date().toISOString();
  let utm = null;
  let gclid = null; //Google click id, analytics will try to capture and save to cookie if present.
  let browser_details = {};
  const source = 'pg';
  if (typeof window.razorpayAnalytics !== 'undefined') {
    utm = window.razorpayAnalytics.utils.getLandingParams();
    gclid = window.razorpayAnalytics.utils.getCookie('gclid');
    if (typeof window.razorpayAnalytics.utils.getBrowserDetails !== 'undefined') {
      browser_details = window.razorpayAnalytics.utils.getBrowserDetails();
    }
  }
  const commonProperties = {
    pageUrl: window.location.href,
    screen,
    eventTimestamp,
    source,
    utm_params: utm,
    gclid,
    email_id: user.email,
    user_id: user.id,
    mid: user.current,
    user_role: user.role,
    business_type: user.business_type,
    activation_status: user.activated,
    is_reg_auto_kyc_enabled: user.isRegAutoKYCEnabled,
    is_instant_activation_enabled: user.isInstantActivationEnabled,
    is_aadhar_ekyc_mandatory: user.isAadharEkycMandatory,
    user_business_category: user.business_category,
    user_business_sub_category: user.business_subcategory,
    device_type: 'mweb',
    new_onboarding_flow: 'yes',
    mode: 'live',
    rzp_mode: getMode(user.id) || '',
    ...browser_details,
    ...properties,
  };
  return commonProperties;
};

export const analyticsTrack = ({
  objectName,
  actionName,
  screen,
  properties = {},
  eventAction,
  activationType = 'kyc',
  user,
  isLJReqiuired = true,
}) => {
  if (!objectName) {
    throw new Error('[analytics]: objectName cannot be empty');
  }

  if (!actionName) {
    throw new Error('[analytics]: actionName cannot be empty');
  }

  if (!screen) {
    throw new Error('[analytics]: screen cannot be empty');
  }

  if (/_/g.test(objectName)) {
    throw new Error(`[analytics]: expected objectName: ${objectName} to not have '_'`);
  }

  if (/_/g.test(actionName)) {
    throw new Error(`[analytics]: expected actionName: ${actionName} to not have '_'`);
  }

  const eventName = titleCase(`${objectName} ${actionName} ${eventAction}`);
  const dataLakeEventName = `${activationType}.${actionName.split(' ').join('_')}`;
  const commonProperties = getCommonProperties({ screen, properties, user });
  if (window.analytics && window.analytics.track) {
    window.analytics.track(eventName, {
      ...commonProperties,
    });
  }
  if (window.rzpQ && window.rzpQ.push && isLJReqiuired) {
    switch (eventAction) {
      case 'initiated':
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated(dataLakeEventName, {
              ...commonProperties,
            }),
        );
        break;
      case 'success':
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .success(dataLakeEventName, {
              ...commonProperties,
            }),
        );
        break;
      case 'failed':
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .failed(dataLakeEventName, {
              ...commonProperties,
            }),
        );
        break;
      case 'dropped':
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .dropped(dataLakeEventName, {
              ...commonProperties,
            }),
        );
        break;
      case 'clicked':
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .clicked(dataLakeEventName, {
              ...commonProperties,
            }),
        );
        break;
      default:
        break;
    }
  }
};
