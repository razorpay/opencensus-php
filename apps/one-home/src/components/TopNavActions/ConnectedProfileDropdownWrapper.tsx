import React from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { logout } from '../../services/api/logoutProfile';
import { switchMerchant } from '../../services/api/switchMerchant';
import { STATUS, isJKOfflineMerchant } from '@libs/shared-utils';
import ConnectedProfileDropdown from './ConnectedProfileDropdown';
import { dispatchWebViewEvent, logoutGoogleAccount } from '@libs/shared-utils';
import { analyticsTrack, getCommonAnalyticsProperties } from '@libs/shared-utils';

function ConnectedProfileDropdownWrapper() {
  const user = useStore((state) => state.session.user);
  const showNotification = useStore((state) => state.showNotification);
  const { trustedBadge, app, org } = useStore((state) => state as any);
  const { badgeStatus } = trustedBadge || {};
  const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
  const { isWebView } = app;

  const logoutUser = () => {
    // if the dashboard is opened in webview for j&k dashboard app
    // then dispatch an event back to app on logout
    // As the session is being maintained in mobile, No need of calling logout at dashboard

    if (isWebView && isJKOfflineMerchant(org, user)) {
      dispatchWebViewEvent({
        eventType: 'LOGOUT',
        data: {},
      });
      return;
    }
    analyticsTrack({
      objectName: 'logout',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'top navigation',
        ...getCommonAnalyticsProperties((window as any).rzp_user),
      },
    });
    // if (this.props.analytics) this.props.analytics('Log Out');
    return logout()
      .catch((e) => {
        analyticsTrack({
          objectName: 'logout',
          actionName: 'result',
          screen: 'home page',
          properties: {
            status: 'failure',
            location: 'top navigation',
            failureReason: e.errors[0],
            ...getCommonAnalyticsProperties((window as any).rzp_user),
          },
        });
        console.error(e);
      })
      .then(() => {
        analyticsTrack({
          objectName: 'logout',
          actionName: 'result',
          screen: 'home page',
          properties: {
            status: 'success',
            location: 'top navigation',
            ...getCommonAnalyticsProperties((window as any).rzp_user),
          },
        });

        return window.location.reload();
      });
  };

  const handleSwitchMerchant = (merchant: any) => {
    analyticsTrack({
      objectName: 'switch merchant',
      actionName: 'selected',
      screen: 'home page',
      properties: {
        new_mid: merchant.id,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    switchMerchant(merchant.id)
      .then(() => {
        analyticsTrack({
          objectName: 'switch merchant',
          actionName: 'result',
          screen: 'home page',
          properties: {
            new_mid: merchant.id,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        window.location.reload();
      })
      .catch((error) => {
        showNotification({
          type: 'error',
          message: error?.errors?.[0] || 'Something went wrong',
        });
      });
  };

  return (
    <ConnectedProfileDropdown
      user={user}
      isRTBEnabled={isRTBEnabled}
      onLogout={logoutUser}
      switchMerchant={handleSwitchMerchant}
    />
  );
}

export default ConnectedProfileDropdownWrapper;
