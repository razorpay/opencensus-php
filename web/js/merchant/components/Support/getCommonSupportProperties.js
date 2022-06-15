import { isMobileDevice } from 'merchant/components/Home/data';
import getMobileDetect from 'common/utils/mobileDetect';
import { getCookie, setCookie } from 'common/utils/cookies';
import uuid from 'uuid';

let source = null;
let linked_id = null;
let user_session_id = null;

// this function will update linked id.
export const generateNewLinkedId = () => {
  linked_id = uuid();
  return linked_id;
};

// this function will return an uuid, that will change on page reload and when user clicks
// on help icon after support session expires
export const getLinkedId = () => {
  if (linked_id === null) {
    return generateNewLinkedId();
  }

  return linked_id;
};

// this function returns support session id
export const getSessionId = () => {
  let session_id = getCookie('support-session-id');

  if (!session_id) {
    // if session id not present on cookie then create new support session id.
    session_id = uuid();
    const now = new Date();
    const minutes = 60; // session will expire in 60 mins.
    now.setTime(now.getTime() + minutes * 60 * 1000);
    setCookie('support-session-id', session_id, now);
  }

  return session_id;
};

// this function will return user session token.
const getUserSessionId = () => {
  if (user_session_id === null) {
    user_session_id = getCookie('XSRF-TOKEN');
  }
  return user_session_id;
};

export const getDeviceSource = () => {
  if (source === null) {
    const isWebView = getMobileDetect().isWebView();
    if (isWebView) {
      const isAndroid = getMobileDetect().isAndroid();
      source = isAndroid ? 'Webview - Android' : 'Webview - iOS';
    } else {
      source = isMobileDevice(1020) ? 'Mobile Dashboard' : 'Dashboard';
    }
  }

  return source;
};

// this function returns common support analytics properties.
export const getCommonSupportProperties = () => {
  return {
    source: getDeviceSource(),
    deviceType: isMobileDevice(1020) ? 'mweb' : 'dweb',
    session_id: getSessionId(),
    linked_id: getLinkedId(),
    user_session_id: getUserSessionId(),
    location: 'Help and Support',
    pageUrl: window.location.href,
    pathname: window.location.pathname,
    activationStatus: window.rzp_user?.activation_status || '',
  };
};
