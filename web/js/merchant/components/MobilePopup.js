import React, { useEffect } from 'react';
import Button from 'common/new-ui/Button';
import LocalStorageService from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import '../../../css/merchant/mobile-popup.styl';

const analytics = (screen, action) => {
  analyticsTrack({
    objectName: 'M-web popup',
    actionName: 'clicked',
    screen: `${screen}`,
    properties: {
      actionName: `${action}`,
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};

export function MobilePopup({ title, subtitle, screen, url, notNowClicked, closeModal }) {
  useEffect(() => {
    analyticsTrack({
      objectName: 'M-web popup',
      actionName: 'displayed',
      screen: `${screen}`,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    window.sessionStorage.setItem(`${screen.toLowerCase()}_mweb_popup`, true);
    LocalStorageService.setItem(`${screen.toLowerCase()}_mweb_popup`, true);
  }, []);

  const notClicked = () => {
    analytics(screen, 'Not now');
    closeModal();
    notNowClicked();
  };

  const useAppClicked = () => {
    analytics(screen, 'Use the app');
    closeModal();
    window.open(url, '_blank');
  };

  return (
    <div>
      <div className="logo-img-div">
        <img src="https://razorpay.com/favicon.png" />
      </div>
      <h2 className="popup-title">{title}</h2>
      <p className="popup-subtitle">{subtitle}</p>
      <div className="app-btn-div">
        <Button.Primary onClick={useAppClicked}>Use The App</Button.Primary>
      </div>
      <Button.Transparent onClick={notClicked}>Not Now</Button.Transparent>
    </div>
  );
}

export function UseAppFooter({ screen, url, closeFooter }) {
  const useAppClicked = () => {
    analytics(screen, 'Use the app');
    closeFooter();
    window.open(url, '_blank');
  };

  return (
    <div className="mobile-popup-fixed-footer">
      <span onClick={useAppClicked}>Use The App</span>
      <button onClick={closeFooter}>
        <i className="i i-close" />
      </button>
    </div>
  );
}
