import React from 'react';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';

const getTitle = (pathname) => {
  let title = 'Dashboard';

  if (pathname && (pathname.includes('app-support') || pathname.includes('ticket-support'))) {
    title = 'Help';
  }
  return title;
};

const WebViewHeader = ({ history }) => {
  const title = getTitle(history?.location?.pathname);
  const handleClose = () => {
    try {
      window.ReactNativeWebView.postMessage(JSON.stringify({ eventType: 'EXIT' }));
    } catch (error) {
      errorService.captureError(error, {
        tags: {
          team: Teams.CARE,
        },
        rank: Ranks.P2,
      });
    }
  };

  const handleBackClick = () => {
    history.goBack();
  };

  const cdnBaseUrl = window.cdnBaseUrl || 'https://cdn.razorpay.com';
  return (
    <header className="webview-header">
      {history.length > 1 && (
        <img
          src={`${cdnBaseUrl}/static/assets/support-page/left-arrow-white.svg`}
          alt="Back"
          className="webview-header-back-icon"
          onClick={handleBackClick}
        />
      )}
      {title}
      <img
        src={`${cdnBaseUrl}/static/assets/support-page/cross.svg`}
        alt="cross"
        className="webview-header-cross-icon"
        onClick={handleClose}
      />
    </header>
  );
};

WebViewHeader.defaultProps = {
  history: {},
};

export default WebViewHeader;
