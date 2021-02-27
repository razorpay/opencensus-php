import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import ShowWhen from 'merchant/components/ShowWhen';
import LocalStorageService from 'common/utils/localStorage';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import Image from 'common/ui/Image';
import ModalHeader from 'common/ui/ModalHeader';
import Group, { GroupItem } from 'common/ui/Group';
import Popover, { PopoverBody } from 'common/ui/Popover';
import debounce from 'common/utils/debounce';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import { logout } from 'merchant/reducers/session';
import SwitchMerchant, {
  SwitchMerchantTypeahead,
} from 'merchant/components/HeaderNav/SwitchMerchant';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import rolesList from 'merchant/helpers/permissions/roles-list';
import logoutGoogleAccount from '../../../common/utils/logoutGoogle';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@withRouter
@connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      user: state.session.user,
    };
  },
  { logout, closeModal, openModal, updateSession },
)
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
    this.props.analytics && this.props.analytics('Log Out');
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

    this.props.openModal({
      size: 'xlarge',
      disableClose: false,
      component: <PartnerOnbr closeModal={this.props.closeModal} disableClose={false} />,
    });
  };

  openTicketModal = () => {
    window.rzpTicketSystem && window.rzpTicketSystem.openModal('#ticket', this.props.analytics);
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

  render() {
    let { user, mode, showMobileNav, showGSTModal, analytics = () => {} } = this.props;
    let merchant = user.merchants[user.current];
    const { showRazorpayxToolTip } = this.state;

    return (
      <Dropdown closeOnClick={false} onShow={this.handleShow} onHide={this.handleHide}>
        <DropdownTrigger
          class={`dropdown-toggle${
            user.isAnnouncementIconEnabled || user.isWhatsNewSectionEnabled
              ? ' dropdown-toggle--large-icon'
              : ''
          }`}
        >
          <i className="i i-profile" />
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
                  additionalCondition={(user) =>
                    !!showGSTModal && user.isAllowedView('profile_gst')
                  }
                >
                  <div className="media media-action" onClick={showGSTModal}>
                    <div className="media-body">GST Details</div>
                  </div>
                </ShowWhen>
                <ShowWhen
                  additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
                >
                  <div class="media media-action">
                    <div class="media-body">
                      <a
                        target="_blank"
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
                    <a href="https://x.razorpay.com" target="_blank">
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
