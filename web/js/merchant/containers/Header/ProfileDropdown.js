import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import LocalStorageService from 'rzp/utils/localStorage';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { openModal, closeModal } from 'rzp/modules/modals';
import Image from 'rzp/ui/Image';
import ModalHeader from 'rzp/ui/ModalHeader';

import { logout, showOrHideTour } from 'merchant/modules/session';
import SubmitFeedback from 'merchant/containers/Header/SubmitFeedback';
import SwitchMerchant, {
  SwitchMerchantTypeahead,
} from 'merchant/components/HeaderNav/SwitchMerchant';

@withRouter
@connect(
  state => {
    return {
      ...state.session,
      ...state.config.config,
      isMobileResolution: state.app.isMobileResolution,
    };
  },
  { logout, closeModal, openModal, showOrHideTour }
)
export default class ProfileDropdown extends Component {
  logout = () => {
    this.props.analytics && this.props.analytics('Log Out');
    return this.props
      .logout()
      .catch(e => {
        console.error(e);
      })
      .then(() => {
        window.location.reload();
      });
  };

  submitFeedback = () => {
    this.props.openModal({
      component: <SubmitFeedback analytics={this.props.analytics} />,
    });
  };

  openSwitchMerchantModal = () => {
    const { user, onSwitchMerchant } = this.props;

    this.props.openModal({
      size: 'small',
      component: (
        <div className="switch-merchant-modal-content">
          <ModalHeader
            title="Switch Merchant"
            onCloseClick={this.props.closeModal}
          />
          <div className="modal-body">
            <SwitchMerchantTypeahead
              user={user}
              onSwitchMerchant={onSwitchMerchant}
            />
          </div>
        </div>
      ),
    });
  };

  showOrHideTour = show => {
    let { analytics = () => {}, showOrHideTour } = this.props;

    analytics('Show - Recent UI Changes');

    if (this.props.history.location.pathname.indexOf('/dashboard') === 0) {
      return showOrHideTour(show);
    }

    LocalStorageService.removeItem('hide_new_analytics_banner');
    this.props.history.push('/dashboard');
  };

  render() {
    let {
      user,
      mode,
      showMobileNav,
      showGSTModal,
      isMobileResolution,
      analytics = () => {},
    } = this.props;
    let merchant = user.merchants[user.current];
    return (
      <Dropdown closeOnClick={false}>
        <DropdownTrigger class="dropdown-toggle">
          {isMobileResolution ? (
            <span className="merchant-logo-preview">
              <Image src={user.logo_url}>
                <img src="/dist/css/assets/business_thumbnail.svg" />
              </Image>
            </span>
          ) : (
            user.name || user.user.name
          )}{' '}
          <span class="caret" />
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
                <div class="media-body">
                  <div class="merchantname">{merchant.name}</div>
                  <div>
                    <small>{merchant.id}</small>
                    <CustomClipboard
                      value={merchant.id}
                      onCopy={() => analytics('Copy - Merchant ID')}
                    >
                      <button
                        class="btn btn-default btn-xs"
                        style={{ marginLeft: '5px' }}
                      >
                        Copy <b>Merchant Id</b>
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              </div>
            )}

            {showMobileNav && (
              <React.Fragment>
                {Object.keys(user.merchants).length > 1 && (
                  <div
                    className="media media-action"
                    onClick={this.openSwitchMerchantModal}
                  >
                    <div className="media-body">Switch Merchant</div>
                  </div>
                )}
                <div className="media media-action" onClick={showGSTModal}>
                  <div className="media-body">GST Details</div>
                </div>
                <div class="media media-action">
                  <div class="media-body">
                    <a target="_blank" href="https://docs.razorpay.com">
                      Documentation
                    </a>
                  </div>
                </div>
              </React.Fragment>
            )}

            <div className="media media-action" onClick={this.submitFeedback}>
              <div className="media-body">Give feedback or suggestions</div>
            </div>

            {mode === 'live' &&
              !showMobileNav &&
              user.isNewAnalyticsEnabled && (
                <div
                  class="media media-action"
                  onClick={() => this.showOrHideTour(true)}
                >
                  <div class="media-left">
                    <div class="media-object">
                      <i class="i i-tour" />
                    </div>
                  </div>
                  <div class="media-body">Show Dashboard Home Tour</div>
                </div>
              )}

            <div class="media loggedin-as">
              <div class="media-body">
                <div>Logged in as</div>
                <p className="account-details">
                  <i class="i i-account" />{' '}
                  <span title={user.user.email}>{user.user.email}</span>
                </p>
                <button
                  class="btn btn-primary logout-btn"
                  onClick={this.logout}
                >
                  Log out
                </button>
              </div>
            </div>
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}
