import { Component } from 'react';
import { connect } from 'react-redux';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import NewUIOnboardingDialog from 'merchant/components/NewUIOnboardingDialog';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { openModal, closeModal } from 'rzp/modules/modals';
import { logout, showOrHideTour } from 'merchant/modules/session';
import { fetchConfig } from 'merchant/modules/config';
import SubmitFeedback from 'merchant/containers/Header/SubmitFeedback';

@connect(
  state => {
    return {
      ...state.session,
      ...state.config.config,
    };
  },
  { logout, fetchConfig, closeModal, openModal, showOrHideTour }
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

  submitFeedback = () => {
    this.props.openModal({
      size: 'small',
      component: <SubmitFeedback />,
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
                  <div class="merchantname">
                    {merchant.name}
                  </div>
                  <div>
                    <small>
                      {merchant.id}
                    </small>
                    <CustomClipboard value={merchant.id}>
                      <button
                        class="btn btn-default btn-xs"
                        style={{ marginLeft: '5px' }}
                      >
                        Copy <b>Merchant Id</b>
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
                <p>
                  <b>
                    {user.user.email}
                  </b>
                </p>
                <button class="btn btn-primary btn-sm" onClick={this.logout}>
                  Log out
                </button>
              </div>
            </div>

            <div
              class="media media-action"
              onClick={() => this.props.showOrHideTour(true)}
            >
              <div class="media-left">
                <div class="media-object">
                  <i class="icon icon-tour" />
                </div>
              </div>
              <div class="media-body">Show Recent UI Changes</div>
            </div>

            <div class="media media-action" onClick={this.submitFeedback}>
              <div class="media-left">
                <div class="media-object">
                  <i class="icon icon-help" />
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
