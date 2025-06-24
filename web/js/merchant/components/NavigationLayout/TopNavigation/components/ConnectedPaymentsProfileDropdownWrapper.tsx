import React, { lazy, Suspense, useEffect, useState } from 'react';
import rTracking from 'react-tracking';
import { useStore } from '@federated/apps/shell/commonStore';
import { withI18Service } from 'common/i18';
import { withSplitzService } from 'common/splitz';
import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import { isJKOfflineMerchant } from '@libs/shared-utils';
import {
  dispatchWebViewEvent,
  logoutGoogleAccount,
  setItemInLocalStorage,
  getItemFromLocalStorage,
  removeItemFromLocalStorage,
  isMobileResolution,
  getCommonAnalyticsProperties,
  analyticsTrack,
} from '@libs/shared-utils';
import { logout, updateSession, switchMerchant } from 'merchant/reducers/session';
import { trustedBadgeTooltipInfo } from './constants';

import { track as trackPartnerOnbr } from 'merchant/views/PartnerDashboard/Onboarding/ga';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { Skeleton, useTheme } from '@razorpay/blade/components';
import { useLocation } from 'react-router-dom';
import { isRTUXHomepageEnabled } from '@dashboards/payments/containers/Home/RTUX/utils';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';
import { error } from 'console';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import errorService from '@razorpay/universe-cli/errorService';

const ActivationRequiredModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'AppWrapperComponent' */ 'merchant/components/ActivationRequiredModal'
    ),
);

// Import PartnerActivationRequiredModal for partner mode switching
const PartnerActivationRequiredModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'PartnerActivationRequiredModal' */ 'merchant/views/PartnerDashboard/Activation/Components/ActivationRequiredModal'
    ),
);

const ConnectedProfileDropdown = lazy(
  () =>
    import(
      /* webpackChunkName: 'ConnectedProfileDropdown' */ '@dashboards/payments/components/ConnectedNavigation/ConnectedProfileDropdown'
    ),
);

// Lazy loading PartnerOnbr
const PartnerOnbr = lazy(
  () =>
    import(
      /* webpackChunkName: 'PartnerOnbr' */ 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr'
    ),
);

function ConnectedPaymentsProfileDropdownWrapper({ logout, switchMerchant, splitz, tracking }) {
  //TODO: Check this weird ts error, partner mode is already defined in PaymentsDashboardSessionReducerType

  const user = useStore((state) => state.session.user);

  const openModal = useStore((state) => state.openModal);
  const closeModal = useStore((state) => state.closeModal);
  const showNotification = useStore((state) => state.showNotification);

  const { session, trustedBadge, app } = useStore((state) => state);
  const { mode, partnerMode, org } = session;
  const { badgeStatus } = trustedBadge || {};
  const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
  const { isWebView } = app;
  const [modeToken, setModeToken] = useState<string | null>(null);
  const partnerModeToken = `rzp_partner_mode`;
  const location = useLocation();
  const [showSwitchMerchantModal, setShowSwitchMerchantModal] = useState<boolean>(false);

  const { theme } = useTheme();

  useEffect(() => {
    const oldModeToken = 'rzp_mode';

    if (window.rzp_user) {
      setModeToken(`${oldModeToken}--${window.rzp_user.current}`);
    }
  }, []);

  //TODO: check if this can move to a common place such that it can be used between existing profile dropdown and this exposed component
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
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    logoutGoogleAccount();
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
            ...getCommonAnalyticsProperties(window.rzp_user),
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
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });

        return window.location.reload();
      });
  };

  const isPartnerModeEnabled =
    location.pathname.startsWith('/partners') && user?.isIndependentPartnerKYCEnabled;

  const handlePartnerModeSwitch = (mode) => {
    const merchantId = user?.current;

    if (mode === 'live' && isPartnerModeEnabled) {
      analyticsTrack({
        objectName: 'Partner KYC Form',
        actionName: 'Opened',
        screen: 'Switch Mode',
        properties: {
          source: 'Live mode',
          section: 'Switch Mode',
          partnerID: merchantId,
        },
      });
    }

    const isPartnerActivated =
      user?.merchants[merchantId]?.partner?.activation_status === 'activated';

    if (mode === 'live' && !isPartnerActivated) {
      analyticsTrack({
        objectName: 'Partner KYC Activation Required Modal',
        actionName: 'Opened',
        screen: 'Switch Mode',
        properties: {
          source: 'Live mode',
          section: 'Pop up',
          partnerID: merchantId,
        },
      });

      const isPartnerKYCActivated = user?.isPartnerKYCActivated;

      const partnerActivationStatus = user?.partnerActivationStatus;

      if (isPartnerKYCActivated) {
        setItemInLocalStorage(partnerModeToken, mode);
        window.location.reload();
      } else {
        openModal({
          size: 'small',
          component: (
            <Suspense fallback={null}>
              <PartnerActivationRequiredModal
                partnerActivationStatus={partnerActivationStatus}
                onCloseClick={closeModal}
              />
            </Suspense>
          ),
        });
      }
    } else {
      setItemInLocalStorage(partnerModeToken, mode);
      window.location.reload();
    }
  };

  const switchMode = (mode, callback = () => {}) => {
    const { abExperiments } = splitz;
    const isRTUXHomepage = isRTUXHomepageEnabled({ user, abExperiments });

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Header',
      eventAction: 'Switch - Mode',
      eventLabel: mode,
    });

    analyticsTrack({
      objectName: 'mode',
      actionName: 'selected',
      screen: 'home page',
      properties: {
        current: mode,
        new: 'test',
        ...(isRTUXHomepage ? { version: 'v2' } : {}),
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: isRTUXHomepage }),
      },
    });

    if (isPartnerModeEnabled) {
      handlePartnerModeSwitch(mode);
      return;
    }
    if (mode === 'live' && !user.isActivated) {
      openModal({
        size: 'small',
        component: (
          <Suspense fallback={null}>
            <ActivationRequiredModal
              user={user}
              onCloseClick={() => {
                closeModal();
              }}
            />
          </Suspense>
        ),
      });
    } else {
      callback();
      setItemInLocalStorage(modeToken as string, mode);
      window.trackHubs?.({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
      window.location.reload();
    }
  };

  const showPartnerIntent = () => {
    const businessTypeName = user.isUnregisteredBusiness ? 'Unregistered' : 'Registered';
    trackPartnerOnbr({
      eventLabel: `Partner Onboarding | Start | Explore Partner Program | ${businessTypeName}`,
    });

    analyticsTrack({
      objectName: 'Explore Partner Program',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'top navigation',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toCleverTap: true,
    });

    openModal({
      size: 'xlarge',
      component: (
        <Suspense fallback={null}>
          <PartnerOnbr closeModal={closeModal} disableClose={false} />
        </Suspense>
      ),
      className: isMobileResolution()
        ? 'partner-onboarding-popup mobile-app-popup'
        : 'partner-onboarding-popup',
    });

    tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.partner_signup.start', {
        merchantId: user.merchant.id,
        clickSource: 'merchant_dashboard',
      }),
    );
  };

  const openSwitchMerchantModal = () => {
    setShowSwitchMerchantModal(true);
  };

  const onSwitchMerchantDismiss = () => {
    setShowSwitchMerchantModal(false);
  };

  const onSwitchMerchant = (merchant) => {
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
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors?.[0],
        });
        console.error('error while switching merchant', errors);
        errorService.captureError(errors?.[0], {
          tags: {
            team: DASHBOARD_TEAMS.CROSS_SELL_EXPERIENCE,
            module: '[@shell]: TopNavigation - onSwitchMerchant',
          },
          rank: DASHBOARD_PRIORITY_RANKS.P0,
        });
      });
  };

  return (
    <Suspense fallback={<Skeleton width="32px" height="32px" borderRadius="medium" />}>
      <ConnectedProfileDropdown
        user={user}
        isRTBEnabled={isRTBEnabled}
        trustedBadgeTooltipInfo={trustedBadgeTooltipInfo}
        mode={mode}
        partnerMode={partnerMode}
        onSwitchMode={switchMode}
        onLogout={logoutUser}
        showPartnerIntent={showPartnerIntent}
        openSwitchMerchantModal={openSwitchMerchantModal}
        showSwitchMerchantModal={showSwitchMerchantModal}
        onSwitchMerchantDismiss={onSwitchMerchantDismiss}
        onSwitchMerchant={onSwitchMerchant}
      />
    </Suspense>
  );
}

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      logout,
      switchMerchant,
    },
    dispatch,
  );
}

export default compose(
  withI18Service,
  withSplitzService,
  connect(null, mapDispatchToProps),
  rTracking(() => window.rzpQ.component('ProfileDropdown')),
)(ConnectedPaymentsProfileDropdownWrapper);
