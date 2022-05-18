import { isMobileDevice } from 'merchant/components/Home/data';
import getMobileDetect from 'common/utils/mobileDetect';
import { getCookie, setCookie } from 'common/utils/cookies';
import uuid from 'uuid';

let source = null;
export const getCommonSupportProperties = () => {
  if (source === null) {
    const isWebView = getMobileDetect().isWebView();

    if (isWebView) {
      const isAndroid = getMobileDetect().isAndroid();
      source = isAndroid ? 'Webview - Android' : 'Webview - iOS';
    } else {
      source = isMobileDevice(1020) ? 'Mobile Dashboard' : 'Dashboard';
    }
  }

  let session_id = getCookie('support-session-id');

  if (!session_id) {
    session_id = uuid();
    const now = new Date();
    const minutes = 30;
    now.setTime(now.getTime() + minutes * 60 * 1000);
    setCookie('support-session-id', session_id, now);
  }

  return {
    source,
    deviceType: isMobileDevice(1020) ? 'mweb' : 'dweb',
    session_id,
    location: 'Help and Support',
    pageUrl: window.location.href,
    pathname: window.location.pathname,
    activationStatus: window.rzp_user?.activation_status || '',
  };
};
