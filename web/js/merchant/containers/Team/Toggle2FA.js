import { Component } from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import { classList } from 'common/util';

import { toggle2FaEnforcement, updateSelfContact } from 'merchant/modules/team';
import { updateSession } from 'merchant/modules/session';
import User from 'merchant/models/User';
import { openModal, closeModal } from 'rzp/modules/modals';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import {
  VerifyOtp,
  AskMobileNumber,
  PasswordVerification,
} from 'merchant/containers/Team/TwoFaModals';

@connect(state => ({ user: state.session.user }), {
  openModal,
  closeModal,
  toggle2FaEnforcement,
  updateSelfContact,
  updateSession,
})
export default class Toggle2FA extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  showModal = component => {
    //Close anyother open modal
    this.props.closeModal();
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

  toggle2FA = flag => {
    //promise that should be resolved to true when all the steps are completed
    let actionCompleted;
    //Any intermediate modal closure or cancel will abort the process
    const abort = () => {
      this.props.closeModal();
      actionCompleted(false);
    };

    const verifyPassword = () => {
      this.showModal(
        <PasswordVerification
          closeModal={this.props.closeModal}
          abort={abort}
          onConfirm={this.props.toggle2FaEnforcement}
          actionCompleted={actionCompleted}
          enable={flag}
          onAllUsers2faSetupRequired={showAllUsers2faSetupRequired.bind(this)}
        />
      );
    };

    const otpVerification = contactMobile => {
      this.showModal(
        <VerifyOtp
          closeModal={abort}
          contactMobile={contactMobile}
          onChangeMobileNumber={verifyMobile}
          updateContactMobile={this.props.updateSelfContact}
          onSuccessfullVerfication={verifyPassword}
        />
      );
    };

    const verifyMobile = () => {
      this.showModal(
        <AskMobileNumber
          closeModal={abort}
          onConfirm={this.props.updateSelfContact}
          onOTPRequired={otpVerification}
        />
      );
    };

    //Hold the toggle state until a final API call is made & resolved
    return new Promise(resolve => {
      actionCompleted = resolve;
      if (flag) {
        const { user: { second_factor_auth_setup } } = this.props.user;

        const action =
          //Check if user has mobile number verified for setup to continue, if yes skip mobile number verification & move to password verification
          second_factor_auth_setup ? verifyPassword : verifyMobile;
        confirmEnable.bind(this)({ action });
      } else {
        confirmDisable.bind(this)({ action: verifyPassword });
      }
    });

    function showAllUsers2faSetupRequired() {
      this.context.confirm({
        header: '2-step verification',
        message:
          'To enable 2-step verification all your team members should have phone numbers associated to their account.',
        abortLabel: 'Close',
        affirmativeLabel: 'Okay',
        abort,
        action: abort,
      });
    }

    function confirmEnable({ action }) {
      this.context.confirm({
        header: 'Enable 2-step verification',
        message:
          'Are you sure you want to enable 2-step verification to all your team members?',
        affirmativeLabel: 'Yes, enable it',
        abort,
        action,
      });
    }

    function confirmDisable({ action }) {
      this.context.confirm({
        header: 'Disable 2-step verification',
        message:
          'Are you want to disable 2-step verification to all your team members?',
        affirmativeLabel: 'Yes, disable it',
        abortLabel: "No, Don't!",
        abort,
        action,
      });
    }
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
