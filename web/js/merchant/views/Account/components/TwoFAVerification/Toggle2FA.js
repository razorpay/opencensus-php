import { Component } from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import RTracking from 'react-tracking';

import { classList } from 'common/utils/rzp-utils';

import { updateSelfContact } from 'merchant/reducers/team';
import { updateSession } from 'merchant/reducers/session';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';

import UpdateSelfContactMobile from 'merchant/views/Account/Profile/components/UpdateSelfContactMobile';
import PasswordVerification from './PasswordVerification';

@connect(state => ({ user: state.session.user }), {
  openModal,
  closeModal,
  // toggleMerchant2FaEnforcement,
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
    const { toggle2FaEnforcement, getToggle2FaSuccessMsg } = this.props;
    return toggle2FaEnforcement(data)
      .then(response => {
        const { second_factor_auth } = response.data;
        const twoFaStatus = second_factor_auth ? 'on' : 'off';
        const message = getToggle2FaSuccessMsg(twoFaStatus);

        this.props.showNotification({
          type: 'success',
          message,
        });
        this.success();
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];
        if (error === 'User 2FA setup is required') {
          // this is for restricted mode merchants
          // when all team members don't have a verified mobile number
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

  onToggleChange = (flag, cb) =>
    this.toggle2FA(flag).then(completed => {
      //Set the sate in redux store to reflect the new changes
      this.props.onToggleComplete(flag);
      if (completed) {
        this.trackEvent(flag);
      }
      cb(completed);
    });

  render() {
    const { twoFaEnabled } = this.props;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          {this.props.renderTitle && this.props.renderTitle()}

          <span class="toggler-btn">
            <SwitchField
              defaultChecked={twoFaEnabled}
              onChange={this.onToggleChange}
              type="prime"
            />
            <strong
              class={classList(
                'm-l',
                twoFaEnabled ? 'text-primary' : 'text-faded'
              )}
            >
              {twoFaEnabled ? 'Enabled' : 'Disabled'}
            </strong>
          </span>
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <div class="description">{this.props.renderDescription()}</div>
          </form>
        </div>
      </div>
    );
  }

  trackEvent = twoFaEnabled => {
    const event = twoFaEnabled ? 'enable' : 'disable';
    this.props.tracking.trackEvent(
      window.rzpQ
        .now()
        .onbr()
        .success(`dash.2fa_${event}`, {
          source: 'Toggle2FA',
          sessionId: window.session_id,
        })
    );
  };
}

export default RTracking(() => {
  return window.rzpQ.component('Toggle2FA');
})(Toggle2FA);
