import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import ShowWhen from 'merchant/components/ShowWhen';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import Image from 'common/ui/Image';
import ModalHeader from 'common/ui/ModalHeader';
import Group, { GroupItem } from 'common/ui/Group';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { logout, updateSession } from 'merchant/reducers/session';
import { SwitchMerchantTypeahead } from 'merchant/components/HeaderNav/SwitchMerchant';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import rolesList from 'merchant/helpers/permissions/roles-list';
import logoutGoogleAccount from '../../../common/utils/logoutGoogle';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import RTracking from 'react-tracking';
import { track as trackPartnerOnbr } from 'merchant/views/PartnerDashboard/Onboarding/ga';

@withRouter
@connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      user: state.session.user,
      isMobileResolution: state.app.isMobileResolution,
      trustedBadge: state.trustedBadge.status,
    };
  },
  { logout, closeModal, openModal, updateSession },
)
@RTracking(() => window.rzpQ.component('ProfileDropdown'))
export default class ProfileDropdown extends Component {
  state = {
    showRazorpayxToolTip: false,
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
    } = this.props;
    const { badgeStatus } = trustedBadge || {};
    const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
    const merchant = user.merchants[user.current];
    const { showRazorpayxToolTip } = this.state;

    return (
      <Dropdown closeOnClick={false} onShow={this.handleShow} onHide={this.handleHide}>
        <DropdownTrigger
          class={`dropdown-toggle${
            !user.isAnnouncementTextEnabled && !user.isWhatsNewTextEnabled
              ? ' dropdown-toggle--large-icon'
              : ''
          }${isRTBEnabled ? ' rtb-user-dropdown' : ''}`}
        >
          {isRTBEnabled ? (
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
                    src="https://cdn.razorpay.com/static/assets/trustedbadge/rtb_user_icon_bg.svg"
                    className="rtb-user-bg-img"
                  />
                  <i className="rtb-nav-icon" />
                  Hey, Trusted Business
                </span>
                <Popover align="bottom" theme="dark">
                  <PopoverBody>
                    <div>
                      You are a trusted business and the Razorpay trusted business badge is now
                      being displayed on checkout for customers to see
                    </div>
                  </PopoverBody>
                </Popover>
              </span>
            </>
          ) : (
            <i className="i i-profile" />
          )}
        </DropdownTrigger>
        <DropdownContent>
          <div class="dropdown-menu ProfileDropdown">
            {user.current && (
              <div class="media">
                <div class="media-left">
                  <div class="media-object">
                    <Image src={user.logo_url}>
                      <img src="/dist/css/assets/business.svg" />
                    </Image>
                  </div>
                </div>
                <div class="media-body merchant-details-container">
                  <div class="merchantname">{merchant.name}</div>
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
                        <button class="btn btn-default btn-xs">Copy Merchant Id</button>
                      </CustomClipboard>
                    </GroupItem>
                  </Group>
                </div>
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
                    !!showGSTModal && userData.isAllowedView('profile_gst')
                  }
                >
                  <div className="media media-action" onClick={showGSTModal}>
                    <div className="media-body">GST Details</div>
                  </div>
                </ShowWhen>
                <ShowWhen
                  additionalCondition={(userData) =>
                    userData.isOrgAllowedFunctionality('external_links')
                  }
                >
                  <div class="media media-action">
                    <div class="media-body">
                      <a
                        target="_blank"
                        rel="noreferrer"
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
              </React.Fragment>
            )}

            {user.isRazorxAnnouncementEnabled && (
              <>
                <div class="media media-action">
                  <div class="media-left">
                    <div class="media-object">
                      <img
                        src="https://cdn.razorpay.com/static/assets/notifs/razorx.svg"
                        alt=""
                        height="24"
                      />
                    </div>
                  </div>
                  <div class="media-body">
                    <a rel="noreferrer" href="https://x.razorpay.com" target="_blank">
                      Go to RazorpayX
                    </a>
                  </div>
                </div>
                {!showMobileNav && showRazorpayxToolTip && user.isRazorxAnnouncementEnabled && (
                  <Popover persistent={true} theme="dark" align="left" class="razorpayx-popover">
                    <PopoverBody>You can switch to RazorpayX Dashboard from here</PopoverBody>
                  </Popover>
                )}
              </>
            )}

            <div class="media loggedin-as">
              <div class="media-body">
                <div>Logged in as</div>
                <p className="account-details">
                  <i class="i i-account" /> <span title={user.user.email}>{user.user.email}</span>
                </p>
                <button
                  class="btn btn-primary logout-btn"
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
            {user.role === rolesList.OWNER && user.partner_type === null && (
              <div class="media loggedin-as">
                <div class="media-body">
                  <p class="small-txt">Partner with us and start earning on every referral</p>

                  <a
                    class="partner-link"
                    style={{ color: '#528ff0', fontSize: '14px' }}
                    onClick={this.showPartnerIntent}
                  >
                    <strong>Explore Partner Program</strong>{' '}
                  </a>
                </div>
              </div>
            )}
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}
