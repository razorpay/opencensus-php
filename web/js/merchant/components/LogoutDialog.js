import React, { useEffect } from 'react';
import qs from 'query-string';

import BgDesktopImage from 'assets/logout/bg-desk.png';
import BgMobileImage from 'assets/logout/bg-mob.png';
import { getMode } from "@libs/shared-utils";

const LogoutDialog = ({ user }) => {
  const sendLjData = (state, event) => {
    let utm, gclid, browserDetails, source, mode;
    try {
      mode = getMode(user?.id);
    } catch (e) {
      // ignore silently
    }
    const query = qs.parse(window.location.search);

    if (query.merchant) {
      source = query.merchant;
    }
    if (typeof window.razorpayAnalytics !== 'undefined') {
      utm = window.razorpayAnalytics.utils.getLandingParams();
      gclid = window.razorpayAnalytics.utils.getCookie('gclid');
      if (typeof window.razorpayAnalytics.utils.getBrowserDetails !== 'undefined') {
        browserDetails = window.razorpayAnalytics.utils.getBrowserDetails();
      }
    }

    const properties = {
      email_id: user?.user?.email,
      user_id: user?.user?.id,
      mid: user?.current,
      user_role: user?.role,
      component: 'Session expire',
      utm_params: utm,
      gclid,
      mode: 'live',
      rzp_mode: mode,
      source,
      referring_url: document.referrer,
      url: document.location.href,
      browser_details: browserDetails,
      sessionId: window.session_id,
      version: window.isOneTapExpOn ? 1.2 : 1.1,
    };

    if (window.rzpQ && window.rzpQ.onbr) {
      if (state === 'success') {
        window.rzpQ.push(window.rzpQ.now().onbr().success(event, properties));
      }
      if (state === 'initiated') {
        window.rzpQ.push(window.rzpQ.now().onbr().initiated(event, properties));
      }
    }
  };

  const trackPopupSuccess = () => {
    sendLjData('success', 'dash.session_expired');
  };

  const trackLoginInitiated = () => {
    sendLjData('initiated', 'dash.expired_modal_login_click');
  };

  useEffect(() => {
    trackPopupSuccess();
    window.setTimeout(() => {
      window.location.reload();
    }, 3000);
  });

  const handleOnClickLogInNow = () => {
    trackLoginInitiated();
    window.location.reload();
  };

  return (
    <div className="logout-dialog">
      <div className="content">
        <h3 className="title">Your session has Expired!</h3>
        <p>You are required to login again since you have been inactive for the past few hours</p>
        <div className="subtext">Redirecting soon...</div>
        <button className="button" onClick={handleOnClickLogInNow}>
          Log In Now <i className="i i-arrow-forward" />
        </button>
      </div>
      <img src={BgDesktopImage} alt="logout-image" className="bg-image desktop" />
      <img src={BgMobileImage} alt="logout-image" className="bg-image mobile" />
    </div>
  );
};

export default LogoutDialog;
