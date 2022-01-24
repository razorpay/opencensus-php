import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import rTracking from 'react-tracking';
import WhatsNew from 'common/ui/WhatsNew/Old';
import NotificationIcon from 'common/ui/WhatsNew/Icon';
import ErrorFallbackComponent from 'common/ui/WhatsNew/ErrorFallbackComponent';
import HighlightTestMode from 'merchant/components/HighlightTestMode';
import { toggleMobileMenu } from 'merchant/reducers/app';
import { isMobileDevice } from 'merchant/components/Home/data';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import ShowWhen from 'merchant/components/ShowWhen';
import { getItem, setItem } from 'common/utils/localStorage';
import NavFragment from './NavFragment';
import AppSwitcher from './AppSwitcher';
import ProfileDropdown from './ProfileDropdown';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import StatusDetails from './StatusDetails/index';
import { compose } from 'redux';
import SupportRequestDropdown from './SupportRequestDropdown';
import SuccessFullCreditModal from 'common/ui/OnboardingCoupons/SuccessFullCreditModal';
import {
  fetchModalConfigDetails,
  updateModalConfigDetails,
} from 'merchant/reducers/ModalConfigApi';
import OnboardingCoupons from 'common/ui/OnboardingCoupons';

// number of times to show MTU offer
const COUNT_TO_SHOW_MTU_OFFER = 5;

const analyticsAction = (action) => {
  window?.rzpAnalytics?.({
    eventCategory: 'Dashboard - Header',
    eventAction: action,
  });
};

function toggleDropdown() {
  document.querySelector('#profile-dropdown .dropdown-toggle').click();
}
class HeaderNav extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isSuccessfullyCouponApplied: false,
      mtuOfferCount: null,
    };

    this.onToggleAppMenu = this.onToggleAppMenu.bind(this);
  }

  fetchMTUOfferConfigDetails = async () => {
    const res = await fetchModalConfigDetails('onboarding');

    if (res?.data) {
      const count = Number(res.data?.mtu_coupon_popup_count);
      const isCouponApplied = Number(res.data?.enable_mtu_congratulatory_popup) ?? 0;
      this.setState({
        mtuOfferCount: count,
        isSuccessfullyCouponApplied: !!isCouponApplied,
      });
    }
  };

  componentDidMount() {
    const hash = this.props.history.location.hash;
    if (hash === '#profile_dropdown') {
      toggleDropdown();
    }

    if (this.props.user.isAppSwitcherEnabled) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().success('dashboard.display_appswitcher', {
          menu_title: 'App Switcher',
          session_id: window.session_id,
        }),
      );
    }
    this.fetchMTUOfferConfigDetails();
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.location.hash !== this.props.location.hash) {
      if (nextProps.location.hash === '#profile_dropdown') {
        toggleDropdown();
      }
    }
  }

  onToggleAppMenu() {
    analyticsAction('Click - Sidebar Toggle');
    this.props.toggleMobileMenu();
  }
  // function to open MTU popup
  showMTUOffer = (isButtonClicked = false) => {
    const { closeModals, openModals, user } = this.props;

    openModals({
      component: (
        <OnboardingCoupons
          closeModal={closeModals}
          mtuCouponCount={this.state.mtuOfferCount}
          autoOpenOnboardingCoupon={user.autoOpenOnboardingCoupon}
          isButtonClicked={isButtonClicked}
        />
      ),
      size: 'xlarge',
    });
  };

  componentDidUpdate(_prevProp, prevState) {
    if (prevState.mtuOfferCount !== this.state.mtuOfferCount) {
      const { user, referee } = this.props;
      const prevSessionID = getItem(`prev_session`);
      const isReferredMerchant = referee?.status === 'signup';
      const canShowOnboardingOffers =
        user.isOnboardingCouponEnabled && !isReferredMerchant && user.showMtuPopup;

      if (
        canShowOnboardingOffers &&
        window.session_id !== prevSessionID &&
        typeof this.state.mtuOfferCount === 'number' &&
        this.state.mtuOfferCount < COUNT_TO_SHOW_MTU_OFFER &&
        user.autoOpenOnboardingCoupon
      ) {
        this.showMTUOffer();
        setItem('prev_session', window.session_id);
      }
    }
  }

  render() {
    const {
      user,
      mode,
      showGSTModal,
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
      showMobileNav,
      analytics,
      activePageName,
      org,
      referee,
    } = this.props;
    const { isSuccessfullyCouponApplied, mtuOfferCount } = this.state;

    const fragmentSpecificProps = {
      mode,
      referee,
      canShowMtuPopup: user.showMtuPopup,
      mtuOfferCount,
    };
    const commonProps = {
      user,
      showGSTModal,
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
    };

    return (
      <div class="nav-wrapper">
        <nav class="navbar navbar-default navbar-fixed-top">
          <div class="container-fluid navbar-container">
            <div className="navbar-collapse" id="headerNav">
              {!showMobileNav && !user.isOrgRZP && !user.isOrgAxis && (
                <img
                  src="/img/branding/powered-by-razorpay-dashboard.png"
                  class="rzp-branding-logo logo-header"
                  alt="Powered by Razorpay"
                />
              )}
              {showMobileNav && (
                <div className="pull-left navbar-toggle-container">
                  <button type="button" className="navbar-toggle" onClick={this.onToggleAppMenu}>
                    <span class="i-bar" />
                    <span class="i-bar" />
                    <span class="i-bar" />
                  </button>{' '}
                  {activePageName || 'Dashboard'}
                </div>
              )}
              <ul className="nav navbar-nav navbar-right">
                {!showMobileNav && (
                  <NavFragment analytics={analytics} {...fragmentSpecificProps} {...commonProps} />
                )}
                {/* Will uncomment later. Please dont block this from going to prod  */}
                {!showMobileNav && user.isMobileSignupCareActive && (
                  <li id="support-request">
                    <SupportRequestDropdown showMobileNav={showMobileNav} />
                  </li>
                )}
                <ShowWhen
                  additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}
                >
                  <li id="whats-new-section">
                    <ErrorBoundary FallbackComponent={ErrorFallbackComponent} resetOnProps>
                      {user.isWhatsNewLazyEnabled ? (
                        <NotificationIcon
                          analytics={analytics}
                          showMobileNav={showMobileNav}
                          {...commonProps}
                        />
                      ) : (
                        <ShowWhen
                          additionalCondition={
                            () => !org.features.includes('disable_announcements') // If the org features array include "disable_announcements" then we hide "Announcement Tab".
                          }
                        >
                          <WhatsNew
                            analytics={analytics}
                            showMobileNav={showMobileNav}
                            {...commonProps}
                          />
                        </ShowWhen>
                      )}
                    </ErrorBoundary>
                  </li>
                </ShowWhen>
                {this.props.user.isOrgRZP && this.props.user.isInternalStatusPageEnabled && (
                  <li id="status-details">
                    <StatusDetails AppMode={mode} showMobileNav={showMobileNav} />
                  </li>
                )}
                <ShowWhen
                  additionalCondition={(usr) =>
                    usr.isAppSwitcherEnabled && usr.isAccepted && !usr.isOrgAxis && !usr.isOrgKotak
                  }
                >
                  <li id="app-switcher">
                    <AppSwitcher analytics={analytics} {...commonProps} />
                  </li>
                </ShowWhen>
                <li id="profile-dropdown">
                  <ProfileDropdown
                    analytics={analytics}
                    showMobileNav={showMobileNav}
                    mode={mode}
                    onSwitchMode={onSwitchMode}
                    {...commonProps}
                  />
                </li>
              </ul>
            </div>
          </div>
        </nav>
        {mode === 'test' && isMobileDevice() && <HighlightTestMode onSwitchMode={onSwitchMode} />}
        {isSuccessfullyCouponApplied && (
          <SuccessFullCreditModal
            onCloseModal={() => {
              updateModalConfigDetails({ enable_mtu_congratulatory_popup: 0 }, 'onboarding');
              this.setState({ isSuccessfullyCouponApplied: false });
            }}
          />
        )}
      </div>
    );
  }
}

const mapStateToProps = (state) => ({
  activePageName: state.app.activePageName,
  user: state.session.user,
  org: state.session.org,
  referee: state.merchantReferral.data.referee,
});

const enhancedComponent = compose(
  withRouter,
  rTracking(() => window.rzpQ.component('HeaderNav')),
  connect(mapStateToProps, { toggleMobileMenu, openModals: openModal, closeModals: closeModal }),
);

export default enhancedComponent(HeaderNav);
