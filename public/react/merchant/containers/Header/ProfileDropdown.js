import { Component } from 'react';
import { connect } from 'react-redux';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import ShowWhen from 'merchant/components/ShowWhen';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { openModal } from 'rzp/modules/modals';
import { logout } from 'merchant/modules/session';
import { fetchConfig } from 'merchant/modules/config';
import SubmitFeedback from 'merchant/containers/Header/SubmitFeedback';

@connect(
  state => {
    return {
      ...state.session,
      ...state.config.config,
    };
  },
  { logout, fetchConfig, openModal }
)
export default class ProfileDropdown extends Component {
  componentWillMount() {
    this.props.fetchConfig();
  }

  logout = () => {
    return this.props.logout().then(() => {
      window.location.reload();
    });
  };

  submitFeedback = ({ revert = false }) => {
    this.props.openModal({
      size: 'small',
      component: <SubmitFeedback revertToOldDesign={revert} />,
    });
  };

  render() {
    let user = this.props.user;
    let merchant = user.merchants[user.current];
    return (
      <Dropdown closeOnClick={false}>
        <DropdownTrigger class="dropdown-toggle">
          {user.name || user.user.name} <span class="caret" />
        </DropdownTrigger>
        <DropdownContent>
          <div class="dropdown-menu ProfileDropdown">
            {user.current &&
              <div class="media">
                <div class="media-left">
                  <div class="media-object">
                    {this.props.logo_url
                      ? <img class="img-responsive" src={this.props.logo_url} />
                      : <i class="icon icon-business" />}
                  </div>
                </div>
                <div class="media-body">
                  <div class="merchantname">{merchant.name}</div>
                  <div>
                    <small>{merchant.id}</small>
                    <CustomClipboard value={merchant.id}>
                      <button
                        class="btn btn-default btn-xs"
                        style={{ marginLeft: '5px' }}
                      >
                        Copy <b>Merchant ID</b>
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              </div>}

            <div class="media">
              <div class="media-left">
                <div class="media-object">
                  <i class="icon icon-account" />
                </div>
              </div>
              <div class="media-body">
                <div>Logged in as</div>
                <p><b>{user.user.email}</b></p>
                <button class="btn btn-primary btn-sm" onClick={this.logout}>
                  Log out
                </button>
              </div>
            </div>

            <div class="media media-action" onClick={this.submitFeedback}>
              <div class="media-left">
                <div class="media-object">
                  <i class="icon icon-help" />
                </div>
              </div>
              <div class="media-body">
                Give feedback or suggestions
              </div>
            </div>

            {/* Don't show for new signups after this timestamp July 13, 5:00pm */}
            {user.isNewUIEnabled && user.created_at < 1499965200
              ? <div
                  class="media media-action"
                  onClick={() => this.submitFeedback({ revert: true })}
                >
                  <div class="media-left">
                    <div class="media-object">
                      <i class="icon icon-undo" />
                    </div>
                  </div>
                  <div class="media-body">
                    Revert to old design
                  </div>
                </div>
              : null}
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}
