import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { removeItem } from 'common/utils/localStorage';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import Image from 'common/ui/Image';
import ModalHeader from 'common/ui/ModalHeader';
import Group, { GroupItem } from 'common/ui/Group';

import { logout, showOrHideTour } from 'merchantLA/reducers/session';
import { SwitchMerchantTypeahead } from 'merchant/components/HeaderNav/SwitchMerchant';
import logoutGoogleAccount from '../../../common/utils/logoutGoogle';

@withRouter
@connect(
  (state) => {
    return {
      ...state.session,
      isMobileResolution: state.app.isMobileResolution,
    };
  },
  { logout, closeModal, openModal, showOrHideTour },
)
export default class ProfileDropdown extends Component {
  logout = () => {
    logoutGoogleAccount();
    if (this.props.analytics) this.props.analytics('Log Out');
    return this.props
      .logout()
      .catch((e) => {
        console.error(e);
      })
      .then(() => {
        window.location.reload();
      });
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

  showOrHideTour = (show) => {
    const { analytics = () => {} } = this.props;

    analytics('Show - Recent UI Changes');

    if (this.props.history.location.pathname.indexOf('/dashboard') === 0) {
      return showOrHideTour(show);
    }

    removeItem('hide_new_analytics_banner');
    this.props.history.push('/dashboard');
    return null;
  };

  render() {
    const {
      user,
      mode,
      showMobileNav,
      isMobileResolution,
      onSwitchMode,
      analytics = () => {},
    } = this.props;
    const merchant = user.merchants[user.current];
    return (
      <Dropdown closeOnClick={false}>
        <DropdownTrigger className="dropdown-toggle">
          {isMobileResolution ? (
            <span className="merchant-logo-preview">
              <Image src={user.logo_url}>
                <img src="/dist/css/assets/business_thumbnail.svg" />
              </Image>
            </span>
          ) : (
            user.name || user.user.name
          )}{' '}
          <span className="caret" />
        </DropdownTrigger>
        <DropdownContent>
          <div className="dropdown-menu ProfileDropdown">
            {user.current && (
              <div className="media">
                <div className="media-left">
                  <div className="media-object">
                    <Image src={user.logo_url}>
                      <img src="/dist/css/assets/business.svg" />
                    </Image>
                  </div>
                </div>
                <div className="media-body merchant-details-container">
                  <div className="merchantname">{merchant.name}</div>
                  <Group>
                    <GroupItem>
                      <small>{merchant.id}</small>
                    </GroupItem>
                    <GroupItem>
                      <CustomClipboard
                        value={merchant.id}
                        onCopy={() => analytics('Copy - Merchant ID')}
                      >
                        <button className="btn btn-default btn-xs">Copy Merchant Id</button>
                      </CustomClipboard>
                    </GroupItem>
                  </Group>
                </div>
              </div>
            )}

            {showMobileNav && (
              <>
                {Object.keys(user.merchants).length > 1 && (
                  <div className="media media-action" onClick={this.openSwitchMerchantModal}>
                    <div className="media-body">Switch Merchant</div>
                  </div>
                )}
                <div className="media media-action">
                  <div className="media-body">
                    <a target="_blank" href="https://razorpay.com/docs" rel="noreferrer noopener">
                      Documentation
                    </a>
                  </div>
                </div>
              </>
            )}

            <div className="media media-action" onClick={this.openTicketModal}>
              <div className="media-body">Raise a request</div>
            </div>
            {/* Test Mode and Live Mode Button Action added for m-web only */}
            {showMobileNav && (
              <div
                className={`media media-action ${mode === 'live' ? 'test-go' : 'live-go'}`}
                onClick={() => onSwitchMode(mode === 'live' ? 'test' : 'live')}
              >
                <div className="media-body">Enable {mode === 'live' ? 'Test' : 'Live'} Mode</div>
              </div>
            )}

            {mode === 'live' && !showMobileNav && user.isNewAnalyticsEnabled && (
              <div className="media media-action" onClick={() => this.showOrHideTour(true)}>
                <div className="media-left">
                  <div className="media-object">
                    <i className="i i-tour" />
                  </div>
                </div>
                <div className="media-body">Show Dashboard Home Tour</div>
              </div>
            )}

            <div className="media loggedin-as">
              <div className="media-body">
                <div>Logged in as</div>
                <p className="account-details">
                  <i className="i i-account" />{' '}
                  <span title={user.user.email}>{user.user.email}</span>
                </p>
                <button className="btn btn-primary logout-btn" onClick={this.logout}>
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
