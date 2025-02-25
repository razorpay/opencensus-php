import { v4 as uuid } from 'uuid';
import readCookie from '../readCookie';
import setCookie from '../setCookie';

const currentPage =
  window.location.hostname.toLowerCase() + (window.location.pathname || '').toLowerCase();

const getFirstPageInSession = (pageUrl) => {
  // Store the first page url when the user lands on razorpay.com
  // This should not change when the user navigates to other pages
  let firstPageURLInSession;
  try {
    firstPageURLInSession = sessionStorage.getItem('first_page');
    if (!firstPageURLInSession) {
      sessionStorage.setItem('first_page', pageUrl);
      firstPageURLInSession = pageUrl;
    }
  } catch (e) {
    firstPageURLInSession = pageUrl;
  }

  return firstPageURLInSession;
};

/*
 * A function to return session related properties
 * commonSessionId : a common session id between website & merchant dashboard
 * isLandingPageSession : tells if the current page is the first page in the session
 * Here session refers to a session between website & merchant dashboard
 * @return {{commonSessionId: string, isLandingPageSession: boolean}}
 */
const getSessionInfo = () => {
  let commonSessionId = '';
  let isLandingPageSession = false;
  try {
    commonSessionId = sessionStorage.getItem('commonSessionId');
    const commonSessionIdCookie = readCookie('commonSessionId');
    const isWebsiteSession = document.referrer.match('^(https?:\\/\\/|(www\\.))?razorpay.com');
    const isDashboardSession = document.referrer.match(
      '^(https?:\\/\\/|(www\\.))?dashboard.razorpay.com',
    );

    /*
    Check if there is no common session present,
    if yes, then populate the same
    if no, check if the session started from website or dashboard's login
    if yes, use website's or dashboard's session id from the cookie
    and make isLandingPageSession as false because the
    session started from website and landing page was website or dashboard
    if no, create a new session id and populate the same in the cookie,
    so that dashboard can consume it and make isLandingPageSession as
    true bcz the session started on dashboard or website
     */
    if (!commonSessionId) {
      if (isWebsiteSession || isDashboardSession) {
        commonSessionId = commonSessionIdCookie;
        isLandingPageSession = false;
      } else {
        sessionStorage.setItem('commonSessionId', uuid());
        commonSessionId = sessionStorage.getItem('commonSessionId');
        setCookie('commonSessionId', commonSessionId);
        isLandingPageSession = getFirstPageInSession(currentPage) === currentPage;
      }
    }
  } catch (e) {
    throw new Error('Session storage is not supported in the browser.');
  }

  return {
    commonSessionId,
    isLandingPageSession,
  };
};

export default getSessionInfo;
