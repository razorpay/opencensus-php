import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import LocalStorageService from 'rzp/utils/localStorage';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { openModal, closeModal } from 'rzp/modules/modals';

import { logout, showOrHideTour } from 'merchant/modules/session';
import SubmitFeedback from 'merchant/containers/Header/SubmitFeedback';

@withRouter
@connect(
  state => {
    return {
      ...state.session,
      ...state.config.config,
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
      size: 'small',
      component: <SubmitFeedback analytics={this.props.analytics} />,
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
    let { user, mode, analytics = () => {} } = this.props;
    let merchant = user.merchants[user.current];
    return (
      <Dropdown closeOnClick={false}>
        <DropdownTrigger class="dropdown-toggle">
          {user.name || user.user.name} <span class="caret" />
        </DropdownTrigger>
        <DropdownContent>
          <div class="dropdown-menu ProfileDropdown">
            {user.current && (
              <div class="media">
                <div class="media-left">
                  <div class="media-object">
                    {this.props.logo_url ? (
                      <img class="img-responsive" src={this.props.logo_url} />
                    ) : (
                      <i class="i-business" />
                    )}
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

            <div class="media">
              <div class="media-left">
                <div class="media-object">
                  <i class="i i-account" />
                </div>
              </div>
              <div class="media-body">
                <div>Logged in as</div>
                <p>
                  <b>{user.user.email}</b>
                </p>
                <button class="btn btn-primary btn-sm" onClick={this.logout}>
                  Log out
                </button>
              </div>
            </div>

            {mode === 'live' &&
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

            <div class="media media-action" onClick={this.submitFeedback}>
              <div class="media-left">
                <div class="media-object">
                  <i class="i i-help" />
                </div>
              </div>
              <div class="media-body">Give feedback or suggestions</div>
            </div>
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}
