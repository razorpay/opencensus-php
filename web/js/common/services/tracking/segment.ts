import errorService from '@razorpay/universe-cli/errorService';
import axios from 'axios';
import { v4 as uuid } from 'uuid';

import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { getMode } from 'common/services/mode';
import { getCookie, setCookie } from 'common/utils/cookies';
import getMobileDetect from 'common/utils/mobileDetect';
import store from 'merchant/store';
import { ANALYTICS } from 'common/constant';

/* Delimiters are space / underscore */
export const titleCase = (sentence) => {
  return (sentence || '')
    .split(/\s+|_/)
    .map((word) => word.charAt(0).toUpperCase() + word.substr(1).toLowerCase())
    .join(' ');
};

const sendToLumberjack = ({ eventName, properties = {} }) => {
  const body = {
    mode: 'live',
    key: window.LUMBERJACK_API_KEY,
    events: [
      {
        event_type: 'pg-dashboard',
        event: eventName,
        event_version: 'v1',
        timestamp: new Date().getTime(),
        properties: {
          ...properties,
        },
      },
    ],
  };

  axios
    .post(window.LUMBERJACK_API_URL, JSON.stringify(body), {
      headers: {
        'Content-Type': 'application/json',
      },
    })
    .catch(() => {});
};

export const getClientID = () => {
  let clientId = getCookie('clientId');
  if (!clientId) {
    clientId = uuid();
    setCookie('clientId', clientId);
  }
  return clientId;
};

const getCommonProperties = ({ screen, properties, user }) => {
  const eventTimestamp = new Date().toISOString();
  let utm = null;
  let gclid = null; //Google click id, analytics will try to capture and save to cookie if present.
  let browser_details = {};
  const source = getMobileDetect()?.isMobile() ? 'Mobile Dashboard' : 'Dashboard';
  const merchantID = store.getState().newAuth.merchantID;

  if (typeof window?.razorpayAnalytics !== 'undefined') {
    utm = window?.razorpayAnalytics.utils.getLandingParams();
    gclid = window?.razorpayAnalytics.utils.getCookie('gclid');
    if (typeof window?.razorpayAnalytics.utils.getBrowserDetails !== 'undefined') {
      browser_details = window.razorpayAnalytics?.utils.getBrowserDetails();
    }
  }
  const commonUserProperties = user
    ? {
        email_id: user?.email,
        user_id: user?.id,
        mid: user?.current,
        user_role: user?.role,
        business_type: user?.business_type,
        activation_status: user?.activated,
        current_activation_status: user?.activation_status,
        previous_activation_status:
          user?.activationStatusChangeLogs?.[user.activationStatusChangeLogs.length - 1],
        is_reg_auto_kyc_enabled: user?.isRegAutoKYCEnabled,
        is_aadhar_ekyc_mandatory: user?.isAadharEkycMandatory,
        is_gstin_mandatory: user?.isGstinMandatory,
        user_business_category: user?.business_category,
        user_business_sub_category: user?.business_subcategory,
        rzp_mode: getMode(user?.id) ?? '',
      }
    : {
        clientId: getClientID(),
        referrer: document.referrer,
        mid: merchantID,
      };
  const commonProperties = {
    pageUrl: window?.location?.href,
    slug: window?.location?.pathname,
    screen,
    eventTimestamp,
    source,
    utm_params: utm,
    gclid,
    device_type: getMobileDetect()?.isMobile() ? 'mweb' : 'dweb',
    new_onboarding_flow: 'yes',
    mode: 'live',
    experiment_ID:
      window.razorpayAnalytics?.utils?.getCookie('auth_source') === 'website'
        ? 'Signup_experiment_1'
        : 'none',
    sessionId: window?.session_id ? window.session_id : undefined,
    ...browser_details,
    ...properties,
    ...commonUserProperties,
  };
  return commonProperties;
};

const throwAnalyticsException = (errorMessage: string) => {
  const error = new Error(errorMessage);

  errorService.captureError(error, {
    tags: {
      team: Teams.PLATFORM,
    },
    rank: Ranks.P2,
  });
};

export const analyticsTrack = ({
  objectName,
  actionName,
  screen = ANALYTICS.SCREEN.DASHBOARD,
  properties = {},
  eventAction = '',
  activationType = 'kyc',
  user,
  isLJReqiuired = true,
  toCleverTap = false,
  toFacebook = false,
}) => {
  const merchantCountry = user?.merchant?.country_code;
  if (merchantCountry === 'SG') {
    return;
  }

  if (!objectName) {
    throw new Error('[analytics]: objectName cannot be empty');
  }

  if (!actionName) {
    throw new Error('[analytics]: actionName cannot be empty');
  }

  if (!screen) {
    throw new Error('[analytics]: screen cannot be empty');
  }

  // Instead of throwing error, replace '_' with '-' in object and action name
  if (/_/g.test(objectName)) {
    throwAnalyticsException(`[analytics]: expected objectName: ${objectName} to not have '_'`);

    return; // Don't capture the event if the objectName contains a "_".
  }

  if (/_/g.test(actionName)) {
    const errorMessage = `[analytics]: expected actionName: ${actionName} to not have '_'`;
    throwAnalyticsException(errorMessage);

    return; // Don't capture the event if the actionName contains a "_".
  }

  const eventName = titleCase(`${objectName} ${actionName}${eventAction ? ` ${eventAction}` : ''}`);
  const dataLakeEventName = `${activationType}.${actionName.split(' ').join('_')}`;
  const commonProperties = getCommonProperties({ screen, properties, user });
  const Facebook = 'Facebook Pixel';

  if (window?.analytics && window?.analytics?.track) {
    window.analytics.track(
      eventName,
      {
        ...commonProperties,
      },
      {
        integrations: {
          CleverTap: toCleverTap,
          [Facebook]: toFacebook,
        },
      },
    );
  }

  //send LJ to pg-dashboard table
  if (isLJReqiuired) {
    sendToLumberjack({
      eventName,
      properties: {
        ...commonProperties,
      },
    });
  }

  if (window?.rzpQ && window?.rzpQ?.push && isLJReqiuired) {
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
        // all default eventAction event goes here
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated(`${dataLakeEventName}_${eventAction}`, {
              ...commonProperties,
            }),
        );
        break;
    }
  }
};
