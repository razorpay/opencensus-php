import React, { Component } from 'react';
import LazyLoad from 'react-lazyload';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { HeadphonesIcon, Box, Button, UserIcon, Text, Heading } from '@razorpay/blade/components';
import BusinessImage from 'assets/business.svg';
import RTBUserIconBg from 'assets/trustedbadge/rtb_user_icon_bg.svg';
import { withRouter } from 'common/deprecated/withRouter';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import Group, { GroupItem } from 'common/ui/Group';
import Image from 'common/ui/Image';
import ModalHeader from 'common/ui/ModalHeader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import logoutGoogleAccount from 'common/utils/logoutGoogle';
import { getCommonAnalyticsProperties, isLoggedInViaMobile } from 'common/utils/rzp-utils';
import { SwitchMerchantTypeahead } from 'merchant/components/HeaderNav/SwitchMerchant';
import ShowWhen from 'merchant/components/ShowWhen';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { isOrgFeatureExist } from 'merchant/models/User';
import { logout, updateSession } from 'merchant/reducers/session';
import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import { track as trackPartnerOnbr } from 'merchant/views/PartnerDashboard/Onboarding/ga';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import PaymentHandleSlug from 'merchant/views/PaymentHandle/components/DropDownSlug';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { withI18Service } from 'common/i18';
import ProfileDropdownV2 from './ProfileDropdownV2';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { dispatchWebViewEvent } from 'common/utils/reactNativeWebView';
import { isJKOfflineMerchant } from '../Sidebar/helpers';

const trustedBadgeTooltipInfo =
  'You are a trusted business and the Razorpay trusted business badge is now being displayed on checkout for customers to see';

@withI18Service
@connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      user: state.session.user,
      isMobileResolution: state.app.isMobileResolution,
      trustedBadge: state.trustedBadge.status,
      isWebView: state.app.isWebView,
    };
  },
  { logout, closeModal, openModal, updateSession },
)
@RTracking(() => window.rzpQ.component('ProfileDropdown'))
class ProfileDropdown extends Component {
  state = {
    showRazorpayxToolTip: false,
    showSwitchMerchantModal: false,
  };

  handleHide = () => {
    // hide profile drop down when in hash mode
    if (location.hash.indexOf('#profile_dropdown') > -1) {
      this.props.history.replace(this.props.location.pathname);
    }
  };

  handleShow = () => {
    const { trustedBadge, tracking, user } = this.props;
    const { badgeStatus } = trustedBadge || {};
    const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
    if (user && isRTBEnabled) {
      tracking.trackEvent(
        window.rzpQ &&
          window.rzpQ.merchantActions().interaction('RTBDashboardHomePageTagDisplayed', {
            merchantId: user?.merchant?.id,
            clickSource: 'merchant_dashboard',
          }),
      );
    }
    setTimeout(() => {
      this.setState({ showRazorpayxToolTip: true });
    }, 500);
  };

  logout = () => {
    const { isWebView, org, user } = this.props;
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
    if (this.props.analytics) this.props.analytics('Log Out');
    return this.props
      .logout()
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

  showPartnerIntent = () => {
    if (window && typeof window.hj === 'function') {
      window.hj('trigger', 'partner_onboarding_started');
      window.hj('tagRecording', ['partner_onboarding_started']);
    }
    const businessTypeName = this.props.user.isUnregisteredBusiness ? 'Unregistered' : 'Registered';
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

    this.props.openModal({
      size: 'xlarge',
      disableClose: false,
      component: <PartnerOnbr closeModal={this.props.closeModal} disableClose={false} />,
      className: this.props.isMobileResolution
        ? 'partner-onboarding-popup mobile-app-popup'
        : 'partner-onboarding-popup',
    });

    this.props.tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.partner_signup.start', {
        merchantId: this.props.user.merchant.id,
        clickSource: 'merchant_dashboard',
      }),
    );
  };

  openTicketModal = () => {
    if (window.rzpTicketSystem) window.rzpTicketSystem.openModal('#ticket', this.props.analytics);
  };

  openSwitchMerchantModal = () => {
    const { user, onSwitchMerchant } = this.props;

    this.props.openModal({
      size: 'small',
      component: (
        <div className="switch-merchant-modal-content">
          <ModalHeader title="Switch Merchant" onCloseClick={this.props.closeModal} />
          <div className="modal-body">
            <SwitchMerchantTypeahead user={user} onSwitchMerchant={onSwitchMerchant} />
          </div>
        </div>
      ),
    });
  };

  openSwitchMerchantModalV2 = () => {
    this.setState({ ...this.state, showSwitchMerchantModal: true });
  };

  static getDerivedStateFromProps(nextProps, prevState) {
    const { trustedBadge, tracking, user } = nextProps;
    const { badgeStatus } = trustedBadge || {};
    const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
    if (user && !prevState.isRTBEnabled && isRTBEnabled) {
      tracking.trackEvent(
        window.rzpQ &&
          window.rzpQ.merchantActions().interaction('RTBDashboardHomePageIconDisplayed', {
            merchantId: user?.merchant?.id,
            clickSource: 'merchant_dashboard',
          }),
      );
      return {
        isRTBEnabled: true,
      };
    }
    return null;
  }

  render() {
    const {
      user,
      showMobileNav,
      showGSTModal,
      analytics = () => {},
      trustedBadge,
      tracking,
      mode,
      onSwitchMode,
      i18: { isConfigTagEnabled },
      isRTUXHomepage,
      splitz,
      onSwitchMerchant,
    } = this.props;
    const { badgeStatus } = trustedBadge || {};
    const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
    const merchant = user.merchants[user.current];
    const { showRazorpayxToolTip, showSwitchMerchantModal } = this.state;

    const isSwitchMerchantV2Enabled = isExperimentEnabled(
      splitz?.abExperiments?.switch_merchant_modal_revamp,
    );

    const { isPosSalesAgent } = checkIfPosSalesAgent({
      user,
      abExperiments: splitz.abExperiments,
    });

    if (isRTUXHomepage && !isPosSalesAgent) {
      return (
        <ProfileDropdownV2
          user={user}
          mode={mode}
          onSwitchMode={onSwitchMode}
          onLogout={this.logout}
          openSwitchMerchantModal={
            isSwitchMerchantV2Enabled
              ? this.openSwitchMerchantModalV2
              : this.openSwitchMerchantModal
          }
          isRTBEnabled={isRTBEnabled}
          trustedBadgeTooltipInfo={trustedBadgeTooltipInfo}
          showPartnerIntent={this.showPartnerIntent}
          showSwitchMerchantModal={showSwitchMerchantModal}
          onSwitchMerchantDismiss={() =>
            this.setState({ ...this.state, showSwitchMerchantModal: false })
          }
          onSwitchMerchant={onSwitchMerchant}
        />
      );
    }

    return (
      <Dropdown closeOnClick={false} onShow={this.handleShow} onHide={this.handleHide}>
        <DropdownTrigger
          className={`dropdown-toggle  dropdown-toggle--large-icon${
            isRTBEnabled && !isPosSalesAgent ? ' rtb-user-dropdown' : ''
          }`}
        >
          {isRTBEnabled && !isPosSalesAgent ? (
            <>
              <span className="visible-xs">
                <i className="rtb-nav-icon" />
              </span>
              <span className="hidden-xs">
                <span
                  onClick={() => {
                    tracking.trackEvent(
                      window.rzpQ &&
                        window.rzpQ
                          .merchantActions()
                          .interaction('RTBDashboardHomePageIconClicked', {
                            merchantId: this.props?.user?.merchant?.id,
                            clickSource: 'merchant_dashboard',
                          }),
                    );
                  }}
                >
                  <img
                    src={RTBUserIconBg}
                    className="rtb-user-bg-img"
                    alt="trust badge"
                    // eslint-disable-next-line react/no-unknown-property
                    fetchpriority="high"
                  />
                  <i className="rtb-nav-icon" />
                  Hey, Trusted Business
                </span>
                <Popover align="bottom" theme="dark">
                  <PopoverBody>
                    <div>{trustedBadgeTooltipInfo}</div>
                  </PopoverBody>
                </Popover>
              </span>
            </>
          ) : (
            <i className="i i-profile" />
          )}
        </DropdownTrigger>
        <DropdownContent>
          <div className="dropdown-menu ProfileDropdown">
            {isPosSalesAgent ? (
              <Box padding="spacing.3" testID="pos-sales-agent-content">
                <Heading marginBottom="spacing.4" color="interactive.text.gray.subtle">
                  Logged in as:{' '}
                </Heading>
                <Box marginBottom="spacing.5" display="flex" alignItems="center">
                  <Box
                    height="30px"
                    width="30px"
                    display="flex"
                    justifyContent="center"
                    alignItems="center"
                    borderRadius="50%"
                    backgroundColor="surface.background.gray.moderate"
                    marginRight="spacing.2"
                  >
                    <UserIcon size="small" color="interactive.icon.gray.subtle" />
                  </Box>
                  <Text color="interactive.text.gray.subtle">{user?.user?.email}</Text>
                </Box>
                <Button onClick={this.logout} isFullWidth>
                  Logout
                </Button>
              </Box>
            ) : (
              <React.Fragment>
                {user.current && (
                  <div className="media">
                    <div className="media-left">
                      <div className="media-object">
                        <Image src={user.logo_url}>
                          <img src={BusinessImage} alt="business" />
                        </Image>
                      </div>
                    </div>
                    <div className="media-body merchant-details-container">
                      <div className="merchantname">{merchant.name}</div>
                      {isRTBEnabled && (
                        <Link
                          onClick={() => {
                            tracking.trackEvent(
                              window.rzpQ &&
                                window.rzpQ
                                  .merchantActions()
                                  .interaction('RTBDashboardHomePageTagClicked', {
                                    merchantId: user?.merchant?.id,
                                    clickSource: 'merchant_dashboard',
                                  }),
                            );
                          }}
                          to="/trustedbadge"
                          className="rtb-text"
                        >
                          Razorpay Trusted Business
                        </Link>
                      )}
                      <Group>
                        <GroupItem>
                          <small>{merchant.id}</small>
                        </GroupItem>
                        <GroupItem>
                          <CustomClipboard
                            value={merchant.id}
                            onCopy={() => {
                              analyticsTrack({
                                objectName: 'user dropdown',
                                actionName: 'clicked',
                                screen: 'home page',
                                properties: {
                                  action: 'Copy Merchant ID',
                                  location: 'top navigation',
                                  ...getCommonAnalyticsProperties(window.rzp_user),
                                },
                                toCleverTap: true,
                              });
                              return analytics('Copy - Merchant ID');
                            }}
                          >
                            <button className="btn btn-default btn-xs">Copy Merchant Id</button>
                          </CustomClipboard>
                        </GroupItem>
                      </Group>
                    </div>
                    {user.isPaymentHandleSplitzEnabled &&
                      !isConfigTagEnabled('profile.razorpay_me') && <PaymentHandleSlug />}
                  </div>
                )}
                {showMobileNav && (
                  <React.Fragment>
                    {Object.keys(user.merchants).length > 1 && (
                      <div className="media media-action" onClick={this.openSwitchMerchantModal}>
                        <div className="media-body">Switch Merchant</div>
                      </div>
                    )}
                    <ShowWhen
                      additionalCondition={(userData) =>
                        !!showGSTModal &&
                        userData.isAllowedView('profile_gst') &&
                        !isConfigTagEnabled('account.gst')
                      }
                    >
                      <div className="media media-action" onClick={showGSTModal}>
                        <div className="media-body">GST Details</div>
                      </div>
                    </ShowWhen>
                    <ShowWhen
                      additionalCondition={(userData) =>
                        userData.isOrgAllowedFunctionality('external_links') &&
                        !isConfigTagEnabled('documentation.documentation')
                      }
                    >
                      <div className="media media-action">
                        <div className="media-body">
                          <a
                            target="_blank"
                            rel="noreferrer noopener"
                            onClick={() => {
                              analyticsTrack({
                                objectName: 'documentation',
                                actionName: 'clicked',
                                screen: 'home page',
                                properties: {
                                  location: 'top navigation',
                                  ...getCommonAnalyticsProperties(window.rzp_user),
                                },
                              });
                            }}
                            href="https://razorpay.com/docs"
                          >
                            Documentation
                          </a>
                        </div>
                      </div>
                    </ShowWhen>
                    {/* Test Mode and Live Mode Button Action added for m-web only */}
                    <div
                      className={`media media-action ${mode === 'live' ? 'test-go' : 'live-go'}`}
                      onClick={() => onSwitchMode(mode === 'live' ? 'test' : 'live')}
                    >
                      <div className="media-body">
                        Enable {mode === 'live' ? 'Test' : 'Live'} Mode
                      </div>
                    </div>
                  </React.Fragment>
                )}
                {user.isRazorxAnnouncementEnabled && (
                  <>
                    <div className="media media-action">
                      <div className="media-left">
                        <div className="media-object">
                          <LazyLoad height={24} once>
                            <img
                              src="https://cdn.razorpay.com/static/assets/notifs/razorx.svg"
                              alt="razorpay experiment"
                              height="24"
                            />
                          </LazyLoad>
                        </div>
                      </div>
                      <div className="media-body">
                        <a rel="noreferrer noopener" href="https://x.razorpay.com" target="_blank">
                          Go to RazorpayX
                        </a>
                      </div>
                    </div>
                    {!showMobileNav && showRazorpayxToolTip && user.isRazorxAnnouncementEnabled && (
                      <Popover
                        persistent={true}
                        theme="dark"
                        align="left"
                        className="razorpayx-popover"
                      >
                        <PopoverBody>You can switch to RazorpayX Dashboard from here</PopoverBody>
                      </Popover>
                    )}
                  </>
                )}
                {!withI18Service('account.support_history') ? (
                  <div
                    className="media media-action"
                    onClick={() => {
                      CreateTicketEmitter.emit('toggle-help-section');
                    }}
                  >
                    <div className="media-left">
                      <div className="media-object">
                        <Box display="flex" alignItems="center" justifyContent="center">
                          <HeadphonesIcon
                            size="large"
                            color="surface.icon.gray.subtle"
                            marginLeft="spacing.2"
                          />
                        </Box>
                      </div>
                    </div>
                    <div className="media-body">Help & Support</div>
                  </div>
                ) : null}
                <div className="media loggedin-as">
                  <div className="media-body">
                    <div>Logged in as</div>
                    <p className="account-details">
                      <i className="i i-account" />
                      {isLoggedInViaMobile() ? (
                        <span title={user.user.contact_mobile}>{user.user.contact_mobile}</span>
                      ) : (
                        <span title={user.user.email}>{user.user.email}</span>
                      )}
                    </p>
                    <button
                      className="btn btn-primary logout-btn"
                      onClick={() => {
                        analyticsTrack({
                          objectName: 'user dropdown',
                          actionName: 'clicked',
                          screen: 'home page',
                          properties: {
                            action: 'Logout',
                            location: 'top navigation',
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                        this.logout();
                      }}
                    >
                      Log out
                    </button>
                  </div>
                </div>
                <ShowWhen
                  additionalCondition={(user) =>
                    user.role === rolesList.OWNER &&
                    user.partner_type === null &&
                    !isOrgFeatureExist('hide_razorpay_text_link') &&
                    !user.isCountrySingapore
                  }
                >
                  <div className="media loggedin-as">
                    <div className="media-body">
                      <p className="small-txt">
                        Partner with us and start earning on every referral
                      </p>

                      <a className="partner-link" onClick={this.showPartnerIntent}>
                        <strong>Explore Partner Program</strong>{' '}
                      </a>
                    </div>
                  </div>
                </ShowWhen>
              </React.Fragment>
            )}
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}

export default withRouter(withSplitzService(ProfileDropdown));
