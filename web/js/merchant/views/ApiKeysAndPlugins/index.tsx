import React, { useEffect, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import {
  getBannerAndModalVisibility,
  fetchMerchantWebsiteDetails,
} from 'merchant/reducers/websitecompliance';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { fetchPayments } from 'merchant/reducers/collection';
import Announcement from 'merchant/components/Announcements/Instant';
import OnboardingCard from 'merchant/views/onboarding/mobile/Screens/Home';
import WebsiteComplianceNudge from 'merchant/views/Account/WebsiteAppDetails/Nudge';
import WebsiteComplianceBanner from 'merchant/components/Announcements/WebsiteCompliance';
import KeysAndPlugins from './KeysAndPlugins';
import SwitchMode from 'merchant/components/HeaderNav/SwitchMode';
import * as ModalActions from 'merchant_common/reducers/modals';
import ActivationRequiredModal from 'merchant/components/ActivationRequiredModal';
import * as LocalStorageService from 'common/utils/localStorage';

const ApiKeysAndPlugins = ({
  // router
  history,
  // from parent
  isFullScreenMode = false,
  // state from redux
  user,
  mode,
  modeFormatted,
  referee,
  limitBreach,
  // actions from redux
  openModal,
  closeModal,
  fetchPayments,
  getBannerAndModalVisibility,
  fetchMerchantWebsiteDetails,
}): JSX.Element => {
  const [payments, setPayments] = useState<any>(null);

  const oldModeToken = 'rzp_mode';
  const modeToken = `${oldModeToken}--${user.current}`;

  useEffect(() => {
    fetchPayments().then((res) => {
      setPayments({ items: res?.data?.items ?? [] });
    });

    if (user.isWebsiteComplianceFlowEnabled) {
      getBannerAndModalVisibility();
      fetchMerchantWebsiteDetails();
    }
  }, []);

  const switchMode = (mode, callback = () => {}) => {
    if (mode === 'live' && !user.isActivated) {
      openModal({
        size: 'small',
        component: <ActivationRequiredModal user={user} onCloseClick={closeModal} />,
      });
    } else {
      callback();
      LocalStorageService.setItem(modeToken, mode);
      window.trackHubs?.({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
      location.reload();
    }
  };

  return (
    <div className="keys-plugins-page-wrapper product-led-onboarding">
      {isFullScreenMode ? null : (
        <>
          <div className="hidden-sm">
            {user.showInstantActivation && (
              <Announcement
                className="hidden-sm"
                mode={mode}
                user={user}
                payments={payments}
                limitBreach={limitBreach}
              />
            )}
            <WebsiteComplianceBanner className="hidden-sm" screen="API Keys & Plugins" />
          </div>
          <div className="display-sm">
            {user.isOnboardingV2Enabled ? <OnboardingCard referee={referee} /> : null}
            <WebsiteComplianceNudge screen="API Keys & Plugins" />
          </div>
        </>
      )}
      <div className="tabbed-container">
        {isFullScreenMode ? (
          <header className="scrollable-tab-header">
            <a className="active" onClick={history.goBack}>
              <i className="i i-arrow-back" /> Back
            </a>

            <div className="btn-toolbar pull-right">
              <SwitchMode
                mode={mode}
                modeFormatted={modeFormatted}
                onSwitchMode={switchMode}
                isTestModeBlocked={user.isTestModeBlocked}
              />
            </div>
          </header>
        ) : (
          <header className="scrollable-tab-header hidden-sm">
            <NavLink className="active" to="#">
              API Keys & Plugins
            </NavLink>
          </header>
        )}
        <div className="content">
          <ErrorBoundary resetOnProps>
            <KeysAndPlugins showProvidedChannels={isFullScreenMode} />
          </ErrorBoundary>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  mode: state.session.mode,
  modeFormatted: state.session.modeFormatted,
  limitBreach: state.home.limitBreach,
  referee: state.merchantReferral.data.referee,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      fetchPayments,
      getBannerAndModalVisibility,
      fetchMerchantWebsiteDetails,
    },
    dispatch,
  );

export default compose(connect(mapStateToProps, mapDispatchToProps)(withRouter(ApiKeysAndPlugins)));
