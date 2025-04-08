/* eslint-disable react/no-unsafe */
import { Box, MenuIcon } from '@razorpay/blade/components';
import PoweredByRzp from 'assets/branding/powered_by_rzp.png';
import React, { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withI18Service } from 'common/i18';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import OffersForYou from 'common/ui/OffersForYou';
import SuccessFullCreditModal from 'common/ui/OnboardingCoupons/SuccessFullCreditModal';
import ErrorFallbackComponent from 'common/ui/WhatsNew/ErrorFallbackComponent';
import NotificationIcon from 'common/ui/WhatsNew/Icon';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { classList } from 'common/utils/rzp-utils';
import HighlightTestMode from 'merchant/components/HighlightTestMode';
import { isMobileDevice } from 'merchant/components/Home/data';
import ShowWhen from 'merchant/components/ShowWhen';
import { isRTUXHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';
import { isOrgFeatureExist } from 'merchant/models/User';
import {
  fetchModalConfigDetails,
  updateModalConfigDetails,
} from 'merchant/reducers/ModalConfigApi';
import { toggleMobileMenu } from 'merchant/reducers/app';
import lazyLoader from 'merchant/routes/LazyLoader';
import EcosystemDowntimes from 'merchant/views/EcosystemDowntimes';

import { isEligibleForReKyc } from '../ReKycStatusAlerts/utils';
import { isJKOfflineMerchant } from '../Sidebar/helpers';
import AppSwitcher from './AppSwitcher';
import NavFragment from './NavFragment';
import ProfileDropdown from './ProfileDropdown';
import StatusDetails from './StatusDetails';
import SupportRequestDropdown from './SupportRequestDropdown';
import UniversalSearch from './UniversalSearch';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';
import { isEligibleForFtuxV2 } from '@dashboards/payments/containers/Home/FTUX/utils';

const WhatsNew = lazyLoader(() =>
  import(/* webpackChunkName: 'merchantWhatsNew' */ 'common/ui/WhatsNew/Old'),
);

const ReKycStatusModal = lazyLoader(() =>
  import(/* webpackChunkName: 'reKycStatusModal' */ 'merchant/components/ReKycStatusAlerts').then(
    (module) => ({ default: module.ReKycStatusModal }),
  ),
);
const NCModal = lazyLoader(() =>
  import(/* webpackChunkName: 'NCModal' */ 'merchant/components/Activation/NCModal'),
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
      isRefreshLoading: false,
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

  isRTUXHomepage() {
    const { abExperiments } = this.props.splitz;
    const { user } = this.props;
    return isRTUXHomepageEnabled({ user, abExperiments });
  }

  isFtuxV2Enabled() {
    const { abExperiments } = this.props.splitz;
    const { user } = this.props;
    return isEligibleForFtuxV2({ user, abExperiments });
  }

  componentDidMount() {
    const hash = this.props.history.location.hash;
    if (hash === '#profile_dropdown') {
      toggleDropdown();
    }

    this.props.tracking.trackEvent(
      window.rzpQ.onbr().success('dashboard.display_appswitcher', {
        menu_title: 'App Switcher',
        session_id: window.session_id,
      }),
    );

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

  goToNCOnEasy = () => {
    const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
    window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
  };

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
      isSidebarV2,
      i18: { isConfigTagEnabled },
      splitz,
    } = this.props;

    const { isSuccessfullyCouponApplied, mtuOfferCount, isRefreshLoading } = this.state;

    const fragmentSpecificProps = {
      mode,
      referee,
      canShowMtuPopup: user.showMtuPopup,
      mtuOfferCount,
    };
    const showNewHomePage = isSidebarV2 && (this.isRTUXHomepage() || this.isFtuxV2Enabled());
    const commonProps = {
      user,
      showGSTModal,
      modeFormatted,
      onSwitchMode,
      onSwitchMerchant,
      showNewHomePage,
    };
    const isUniversalSearchEnabled = user.isUniversalSearchEnabled;
    const isMobileSearch = isUniversalSearchEnabled && isMobile;
    const { isPosSalesAgent, isPosEkycAgent } = checkIfPosSalesAgent({
      user,
      abExperiments: splitz.abExperiments,
    });

    const isJKOrg = isJKOfflineMerchant(org, user);
    const shouldShowReKycModal = isEligibleForReKyc(splitz, user);

    return (
      <div className="nav-wrapper">
        <nav
          className={classList(
            'navbar navbar-default navbar-fixed-top',
            isMobileSearch && 'search-nav-box',
            showNewHomePage && 'homepage-rtux-navbar',
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
              {isMobile ? (
                <div className="pull-left navbar-toggle-container">
                  {showNewHomePage ? (
                    <Box
                      display="flex"
                      alignItems="center"
                      justifyContent="center"
                      height="60px"
                      testID="header-menu-icon"
                    >
                      <MenuIcon
                        size="medium"
                        onClick={this.onToggleAppMenu}
                        color="interactive.icon.gray.subtle"
                        margin="spacing.2"
                      />
                    </Box>
                  ) : (
                    <>
                      <button
                        type="button"
                        className="navbar-toggle"
                        onClick={this.onToggleAppMenu}
                      >
                        <span className="i-bar" />
                        <span className="i-bar" />
                        <span className="i-bar" />
                      </button>{' '}
                      {isPosSalesAgent || isPosEkycAgent ? null : activePageName || 'Dashboard'}
                    </>
                  )}
                </div>
              ) : null}
              {!isPosSalesAgent && !isPosEkycAgent ? (
                <React.Fragment>
                  {isUniversalSearchEnabled && !isMobile && (
                    <div className="universal-search-desktop">
                      <UniversalSearch showNewHomePage={showNewHomePage} />
                    </div>
                  )}
                  <ul className="nav navbar-nav navbar-right">
                    {!showMobileNav && (
                      <NavFragment
                        analytics={analytics}
                        {...fragmentSpecificProps}
                        {...commonProps}
                      />
                    )}

                    <ShowWhen
                      additionalCondition={(_user) =>
                        !isConfigTagEnabled('announcements.announcements') &&
                        !isMobileDevice() &&
                        !showNewHomePage
                      }
                    >
                      <GrowthAssetEB>
                        <ShowWhen
                          additionalCondition={() => user.isProjectNitroEnabled && showMobileNav}
                        >
                          <OffersForYou
                            showMobileNav={showMobileNav}
                            mtuOfferCount={mtuOfferCount}
                          />
                        </ShowWhen>
                      </GrowthAssetEB>
                    </ShowWhen>

                    {/* Will uncomment later. Please dont block this from going to prod  */}
                    {!showNewHomePage && !showMobileNav && user.isMobileSignupCareActive && (
                      <li id="support-request">
                        <SupportRequestDropdown showMobileNav={showMobileNav} />
                      </li>
                    )}

                    <ShowWhen
                      additionalCondition={(_user) =>
                        _user.isOrgAllowedFunctionality('external_links') &&
                        !isConfigTagEnabled('announcements.announcements')
                      }
                    >
                      <li id="whats-new-section" data-testid="header-announcement">
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
                    {user?.isOrgRZP && user.isCountryIndia && (
                      <li id="status-details" data-testid="header-status-details">
                        {user.isEcosystemDowntimeEnabled ? (
                          <EcosystemDowntimes
                            mode={mode}
                            showMobileNav={showMobileNav}
                            showNewHomePage={showNewHomePage}
                          />
                        ) : (
                          <StatusDetails
                            AppMode={mode}
                            showMobileNav={showMobileNav}
                            showNewHomePage={showNewHomePage}
                          />
                        )}
                      </li>
                    )}

                    <ShowWhen
                      additionalCondition={(_user) =>
                        !showNewHomePage &&
                        _user?.isAccepted &&
                        !_user?.isOrgAxis &&
                        !_user?.isOrgKotak &&
                        !isOrgFeatureExist('hide_razorpay_text_link') &&
                        !isConfigTagEnabled('app_switcher.app_switcher') &&
                        !isJKOrg
                      }
                    >
                      <li id="app-switcher">
                        <AppSwitcher analytics={analytics} {...commonProps} />
                      </li>
                    </ShowWhen>
                    <li id="profile-dropdown" data-testid="profile-dropdown">
                      <ProfileDropdown
                        analytics={analytics}
                        showMobileNav={showMobileNav}
                        mode={mode}
                        onSwitchMode={onSwitchMode}
                        {...commonProps}
                      />
                    </li>
                  </ul>
                </React.Fragment>
              ) : (
                <ul className="nav navbar-nav navbar-right">
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
              )}
            </div>
          </div>
        </nav>
        {!isPosSalesAgent && !isPosEkycAgent ? (
          <React.Fragment>
            {isMobileSearch && (
              <div
                className={classList('mobile-search-layout', showNewHomePage && 'homepage-rtux-navbar')}
              >
                <UniversalSearch showNewHomePage={showNewHomePage} />
              </div>
            )}
            {mode === 'test' && isMobileDevice() && (
              <HighlightTestMode onSwitchMode={onSwitchMode} />
            )}
            {isSuccessfullyCouponApplied && (
              <SuccessFullCreditModal
                onCloseModal={() => {
                  updateModalConfigDetails({ enable_mtu_congratulatory_popup: 0 }, 'onboarding');
                  this.setState({ isSuccessfullyCouponApplied: false });
                }}
              />
            )}
          </React.Fragment>
        ) : null}
        {shouldShowReKycModal ? (
          <Suspense fallback={null}>
            <ReKycStatusModal />
          </Suspense>
        ) : null}

        {user.isBddNcRequired ? (
          <Suspense fallback={null}>
            <NCModal
              activationState="needs_clarification_with_payment_disabled"
              goToNCOnEasy={this.goToNCOnEasy}
              user={user}
            />
          </Suspense>
        ) : null}
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
  withSplitzService,
  rTracking(() => window.rzpQ.component('HeaderNav')),
  connect(mapStateToProps, {
    toggleMobileMenu,
  }),
);

export default enhancedComponent(withI18Service(withSplitzService(HeaderNav)));
