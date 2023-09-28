/* eslint-disable react/no-unsafe */
import PoweredByRzp from 'assets/branding/powered_by_rzp.png';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import OffersForYou from 'common/ui/OffersForYou';
import OnboardingCoupons from 'common/ui/OnboardingCoupons';
import SuccessFullCreditModal from 'common/ui/OnboardingCoupons/SuccessFullCreditModal';
import ErrorFallbackComponent from 'common/ui/WhatsNew/ErrorFallbackComponent';
import NotificationIcon from 'common/ui/WhatsNew/Icon';
import { getItem, setItem } from 'common/utils/localStorage';
import { classList } from 'common/utils/rzp-utils';
import HighlightTestMode from 'merchant/components/HighlightTestMode';
import { isMobileDevice } from 'merchant/components/Home/data';
import ShowWhen from 'merchant/components/ShowWhen';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { isOrgFeatureExist } from 'merchant/models/User';
import { toggleMobileMenu } from 'merchant/reducers/app';
import {
  fetchModalConfigDetails,
  updateModalConfigDetails,
} from 'merchant/reducers/ModalConfigApi';
import lazyLoader from 'merchant/routes/LazyLoader';
import EcosystemDowntimes from 'merchant/views/EcosystemDowntimes';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import rTracking from 'react-tracking';
import { compose } from 'redux';
import AppSwitcher from './AppSwitcher';
import NavFragment from './NavFragment';
import ProfileDropdown from './ProfileDropdown';
import StatusDetails from './StatusDetails';
import SupportRequestDropdown from './SupportRequestDropdown';
import UniversalSearch from './UniversalSearch';

const WhatsNew = lazyLoader(() =>
  import(/* webpackChunkName: 'merchantWhatsNew' */ 'common/ui/WhatsNew/Old'),
);

// number of times to show MTU offer
const COUNT_TO_SHOW_MTU_OFFER = 5;

const analyticsAction = (action) => {
  window.rzpAnalytics?.({
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

  UNSAFE_componentWillReceiveProps(nextProps) {
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

      const showMtu =
        canShowOnboardingOffers &&
        window.session_id !== prevSessionID &&
        typeof this.state.mtuOfferCount === 'number' &&
        this.state.mtuOfferCount < COUNT_TO_SHOW_MTU_OFFER &&
        user.autoOpenOnboardingCoupon;

      if (showMtu) {
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
      isMobile,
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
    const isUniversalSearchEnabled = user.isUniversalSearchEnabled;
    const isMobileSearch = isUniversalSearchEnabled && isMobile;
    return (
      <div className="nav-wrapper">
        <nav
          className={classList(
            'navbar navbar-default navbar-fixed-top',
            isMobileSearch && 'search-nav-box',
          )}
        >
          <div className="container-fluid navbar-container">
            <div className="navbar-collapse" id="headerNav">
              {!showMobileNav && !user.isOrgRZP && !user.isOrgAxis && (
                <img
                  src={PoweredByRzp}
                  className="rzp-branding-logo logo-header"
                  alt="Powered by Razorpay"
                />
              )}
              {isMobile && (
                <div className="pull-left navbar-toggle-container">
                  <button type="button" className="navbar-toggle" onClick={this.onToggleAppMenu}>
                    <span className="i-bar" />
                    <span className="i-bar" />
                    <span className="i-bar" />
                  </button>{' '}
                  {activePageName || 'Dashboard'}
                </div>
              )}
              {isUniversalSearchEnabled && !isMobile && (
                <div className="universal-search-desktop">
                  <UniversalSearch />
                </div>
              )}
              <ul className="nav navbar-nav navbar-right">
                {!showMobileNav && (
                  <NavFragment analytics={analytics} {...fragmentSpecificProps} {...commonProps} />
                )}

                <ShowWhen
                  additionalCondition={(_user) =>
                    !_user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Announcements) &&
                    !isMobileDevice()
                  }
                >
                  <GrowthAssetEB>
                    <ShowWhen
                      additionalCondition={() => user.isProjectNitroEnabled && showMobileNav}
                    >
                      <OffersForYou showMobileNav={showMobileNav} mtuOfferCount={mtuOfferCount} />
                    </ShowWhen>
                  </GrowthAssetEB>
                </ShowWhen>

                {/* Will uncomment later. Please dont block this from going to prod  */}
                {!showMobileNav && user.isMobileSignupCareActive && (
                  <li id="support-request">
                    <SupportRequestDropdown showMobileNav={showMobileNav} />
                  </li>
                )}

                <ShowWhen
                  additionalCondition={(_user) =>
                    _user.isOrgAllowedFunctionality('external_links') &&
                    !_user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Announcements)
                  }
                >
                  <li id="whats-new-section">
                    <GrowthAssetEB FallbackComponent={ErrorFallbackComponent}>
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
                          <SuspenseWithLoader type="default">
                            <WhatsNew
                              analytics={analytics}
                              showMobileNav={showMobileNav}
                              {...commonProps}
                            />
                          </SuspenseWithLoader>
                        </ShowWhen>
                      )}
                    </GrowthAssetEB>
                  </li>
                </ShowWhen>
                {user?.isOrgRZP && user?.isInternalStatusPageEnabled && (
                  <li id="status-details">
                    {user.isEcosystemDowntimeEnabled ? (
                      <EcosystemDowntimes mode={mode} showMobileNav={showMobileNav} />
                    ) : (
                      <StatusDetails AppMode={mode} showMobileNav={showMobileNav} />
                    )}
                  </li>
                )}

                <ShowWhen
                  additionalCondition={(_user) =>
                    _user?.isAppSwitcherEnabled &&
                    _user?.isAccepted &&
                    !_user?.isOrgAxis &&
                    !_user?.isOrgKotak &&
                    !isOrgFeatureExist('hide_razorpay_text_link') &&
                    !_user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.AppSwitcher)
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
        {isMobileSearch && (
          <div className="mobile-search-layout">
            <UniversalSearch />
          </div>
        )}
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
  isMobile: state.app.isMobileResolution,
});

const enhancedComponent = compose(
  withRouter,
  rTracking(() => window.rzpQ.component('HeaderNav')),
  connect(mapStateToProps, { toggleMobileMenu, openModals: openModal, closeModals: closeModal }),
);

export default enhancedComponent(HeaderNav);
