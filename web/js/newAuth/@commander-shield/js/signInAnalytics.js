import { getAttribUtmData, getGclid, getRawUtmData } from '../utils/getUtmData';
import getSessionInfo from '../utils/getSessionInfo';
import getIsLandingPageUser from '../utils/getIsLandingPageUser';
import { getCaptchaVariant } from '../utils/captchaService';
import featureFlags from '../utils/featureFlags';
import trackEvents from './analytics';

const utmData = getAttribUtmData();
const newSignInVersion = '2.0';
const mobileOTPFlowVersion = '2.1';

const eventTypes = {
  INITIATED: 'initiated',
  FAILED: 'failed',
  SUCCESS: 'success',
};

const sessionInfo = getSessionInfo();
const getCommonProps = () => {
  return {
    sessionId: window.session_id,
    ref_url: document.referrer,
    url: document.location.href,
    service: 'PG',
    utm_params: getRawUtmData(),
    gclid: getGclid(),
    source: 'sign_in',
    first_utm: utmData.firstUtm,
    last_utm: utmData.lastUtm,
    first_page: utmData.firstPage,
    final_page: utmData.finalPage,
    website: utmData.website,
    referring_url: utmData.website,
    is_landing_page_user: getIsLandingPageUser(),
    is_landing_page_session: sessionInfo.isLandingPageSession,
    common_session_id: sessionInfo.commonSessionId,
    captcha_variant: getCaptchaVariant(),
  };
};

/**
 * Function to send events to lumberjack and segment
 * The segment event is also being send.
 * Check analytics.js
 * @param {String} type Type of the event (initiated, success, failed)
 * @param {String} label Event Name (Login, Create account etc.)
 * @param {Object} data Event properties
 */
const sendSignInEvents = (type, label, data) => {
  const eventData = {
    ...data,
    ...getCommonProps(),
  };
  const signInVersion = featureFlags.ENABLE_MOBILE_OTP_FLOW
    ? mobileOTPFlowVersion
    : newSignInVersion;

  trackEvents.dataLake({
    type,
    eventName: label,
    data: eventData,
    prefix: 'login',
    version: signInVersion,
  });
};

export const sendSignInSuccessEvent = (label, data) => {
  sendSignInEvents(eventTypes.SUCCESS, label, data);
};

export const sendSignInInitiatedEvent = (label, data) => {
  sendSignInEvents(eventTypes.INITIATED, label, data);
};

export const sendSignInFailureEvent = (label, data) => {
  sendSignInEvents(eventTypes.FAILED, label, data);
};

export const sendGeoLocationCaptureSuccessEvent = (label, data) => {
  sendSignInEvents(eventTypes.SUCCESS, label, data);
};

export const sendGeoLocationFailureEvent = (label, data) => {
  sendSignInEvents(eventTypes.FAILED, label, data);
};
