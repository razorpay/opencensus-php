import { Component } from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import RTracking from 'react-tracking';
import { classList } from 'common/utils/rzp-utils';
import User from 'merchant/models/User';

import {
  toggle2FaEnforcement,
  updateSelfContact,
} from 'merchant/reducers/team';
import { updateSession } from 'merchant/reducers/session';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import {
  VerifyOtp,
  AskMobileNumber,
  PasswordVerification,
} from 'merchant/views/Account/ManageTeam/components/TwoFaModals';
import UpdateSelfContactMobile from 'merchant/views/Account/Profile/components/UpdateSelfContactMobile';

@connect(state => ({ user: state.session.user }), {
  openModal,
  closeModal,
  toggle2FaEnforcement,
  updateSelfContact,
  updateSession,
  showNotification,
})
class Toggle2FA extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  showModal = component => {
    this.props.openModal({
      size: 'small',
      component: component,
    });
  };

  //Set the sate in redux store to reflect the new changes
  update2FAState = flag => {
    const { user: currentUser } = this.props.user;
    const user = new User({
      ...this.props.user,
      user: { ...currentUser, second_factor_auth_enforced: flag },
    });
    this.props.updateSession({ user });
  };

  verifyPassword = flag => {
    this.showModal(
      <PasswordVerification
        closeModal={this.abort}
        onSubmit={this.onPasswordSubmit}
        dataSentWithPassword={{ second_factor_auth: flag }}
        enable={flag}
      />
    );
  };

  onPasswordSubmit = data => {
    return this.props
      .toggle2FaEnforcement(data)
      .then(response => {
        const { second_factor_auth } = response.data;
        const message = `2-step verification successfully turned ${
          second_factor_auth ? 'on' : 'off'
        } for all your team members`;

        this.props.showNotification({
          type: 'success',
          message,
        });
        this.success();
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];
        if (error === 'User 2FA setup is required') {
          this.props.closeModal();
          this.showAllUsers2faSetupRequired();
        } else {
          this.props.showNotification({
            type: 'error',
            message: error,
          });
        }
      });
  };

  verifyMobile = flag => {
    this.showModal(
      <UpdateSelfContactMobile
        onSuccess={() => {
          this.verifyPassword(flag);
        }}
        onClose={this.abort}
      />
    );
  };

  success = () => {
    this.props.closeModal();
    this.actionCompleted(true);
  };

  abort = () => {
    this.props.closeModal();
    //Any intermediate modal closure or cancel will abort the process
    this.actionCompleted(false);
  };

  showAllUsers2faSetupRequired = () => {
    this.context.confirm({
      header: '2-step verification',
      message:
        'To enable 2-step verification all your team members should have phone numbers associated to their account.',
      abortLabel: 'Close',
      affirmativeLabel: 'Okay',
      abort: this.abort,
      action: this.abort,
    });
  };

  confirmEnable = ({ action, flag }) => {
    this.context.confirm({
      header: 'Enable 2-step verification',
      message:
        'Are you sure you want to enable 2-step verification to all your team members?',
      affirmativeLabel: 'Yes, enable it',
      abort: this.abort,
      action: () => action(flag),
    });
  };

  confirmDisable({ action, flag }) {
    this.context.confirm({
      header: 'Disable 2-step verification',
      message:
        'Are you want to disable 2-step verification to all your team members?',
      affirmativeLabel: 'Yes, disable it',
      abortLabel: "No, Don't!",
      abort: this.abort,
      action: () => action(flag),
    });
  }

  toggle2FA = flag => {
    //Hold the toggle state until a final API call is made & resolved
    return new Promise(resolve => {
      this.actionCompleted = resolve;
      if (flag) {
        const { user: { second_factor_auth_setup } } = this.props.user;

        const action =
          //Check if user has mobile number verified for setup to continue, if yes skip mobile number verification & move to password verification
          second_factor_auth_setup ? this.verifyPassword : this.verifyMobile;
        this.confirmEnable({ action, flag });
      } else {
        this.confirmDisable({ action: this.verifyPassword, flag });
      }
    });
  };

  render() {
    const { user: { second_factor_auth_enforced } } = this.props.user;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">
            <i class="i i-phonelink-lock" /> 2-Step verification to the team
          </span>
          <span class="toggler-btn">
            <SwitchField
              defaultChecked={second_factor_auth_enforced}
              onChange={(flag, cb) =>
                this.toggle2FA(flag).then(completed => {
                  this.update2FAState(flag);
                  if (completed) {
                    if (second_factor_auth_enforced) {
                      this.props.tracking.trackEvent(
                        window.rzpQ
                          .now()
                          .onbr()
                          .success('dash.2fa_disable', {
                            source: 'Toggle2FA',
                            sessionId: window.session_id,
                          })
                      );
                    } else {
                      this.props.tracking.trackEvent(
                        window.rzpQ
                          .now()
                          .onbr()
                          .success('dash.2fa_enable', {
                            source: 'Toggle2FA',
                            sessionId: window.session_id,
                          })
                      );
                    }
                  }
                  cb(completed);
                })
              }
              type="prime"
            />
            <strong
              class={classList(
                'm-l',
                second_factor_auth_enforced ? 'text-primary' : 'text-faded'
              )}
            >
              {second_factor_auth_enforced ? 'Enabled' : 'Disabled'}
            </strong>
          </span>
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <div class="description">
              <p>
                2-step verification will be enforced to all the team members who
                have access to this Dashboard.
              </p>
              <p>
                <strong>Note:</strong> This setting requires 2-step verification
                set up on your account
              </p>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default RTracking((state, props, args) => {
  return window.rzpQ.component('Toggle2FA');
})(Toggle2FA);
