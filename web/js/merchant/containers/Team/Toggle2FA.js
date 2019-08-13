import { Component } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import { toggle2FaEnforcement, updateSelfContact } from 'merchant/modules/team';
import { updateSession } from 'merchant/modules/session';
import User from 'merchant/models/User';
import { openModal, closeModal } from 'rzp/modules/modals';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import {
  VerifyOtp,
  MissingNumbers, //This component is supposed to be rendered,when not all team-members have mobile number. Having only a frontend check is not sufficient.
  AskMobileNumber,
  EnableAgreement,
  DisableAgreement,
  PasswordVerification,
} from 'merchant/containers/Team/TwoFaModals';

@connect(
  state => {
    return {
      session: state.session,
    };
  },
  {
    updateFeatures,
    showNotification,
    openModal,
    closeModal,
    toggle2FaEnforcement,
    updateSelfContact,
    updateSession,
  }
)
export default class Toggle2FA extends Component {
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
    const {
      user: { user: currentUser },
      mode,
    } = this.props.session;
    const user = new User({
      ...this.props.session.user,
      user: { ...currentUser, second_factor_auth_enforced: flag },
    });
    this.props.updateSession({
      user,
      mode,
    });
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
      const title = `${flag ? 'Enable' : 'Disable'}   2-step verification`;
      this.showModal(
        <PasswordVerification
          title={title}
          closeModal={abort}
          email={this.props.session.user.user.email}
          onConfirm={password =>
            this.props.toggle2FaEnforcement(flag ? 1 : 0, password).then(
              res => {
                this.props.closeModal();
                let message = `2-step verification successfully turned-${
                  res.data.second_factor_auth ? 'on' : 'off'
                } to all your team members`;
                this.props.showNotification({
                  type: 'success',
                  message: message,
                });
                //Resolve with true if user has completed the action
                actionCompleted(true);
              },
              err => {
                this.props.closeModal();
                this.props.showNotification({
                  type: 'error',
                  message: err.errors.shift(),
                });
                actionCompleted(false);
              }
            )
          }
        />
      );
    };
    const otpVerification = (mobile, verificationStatus, otp) => {
      this.showModal(
        <VerifyOtp
          closeModal={abort}
          mobile={mobile}
          onChangeMobileNumber={verifyMobile}
          verified={verificationStatus}
          otp={otp}
          onResend={() => {
            return this.props.updateSelfContact({ contact_mobile: mobile });
          }}
          onConfirm={otp => {
            return this.props
              .updateSelfContact({ contact_mobile: mobile, otp: otp.trim() })
              .then(
                res => {
                  verifyPassword();
                },
                err => {
                  otpVerification(mobile, false, otp);
                }
              );
          }}
        />
      );
    };
    const verifyMobile = () => {
      this.showModal(
        <AskMobileNumber
          closeModal={abort}
          onComplete={mobile => {
            return this.props
              .updateSelfContact({ contact_mobile: mobile })
              .then(
                res => {
                  //With the current design API will always fail if, attempted without OTP
                  //Hence,this will never be called, it's a design constraint as of now.
                },
                err => {
                  otpVerification(mobile);
                }
              );
          }}
        />
      );
    };
    //Hold the toggle state until a final API call is made & resolved
    return new Promise(resolve => {
      actionCompleted = resolve;
      if (flag) {
        let {
          user: {
            user: { second_factor_auth_setup },
          },
        } = this.props.session;
        const afterAgreement =
          //Check if user has mobile number verified for setup to continue, if yes skip mobile number verification & move to password verification
          second_factor_auth_setup === true ? verifyPassword : verifyMobile;
        this.showModal(
          <EnableAgreement closeModal={abort} onAgree={afterAgreement} />
        );
      } else {
        this.showModal(
          <DisableAgreement closeModal={abort} onAgree={verifyPassword} />
        );
      }
    });
  };

  render() {
    let {
      user: {
        user: { second_factor_auth_enforced },
      },
    } = this.props.session;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">
            <i class="i i-phonelink-lock"></i> &nbsp; 2-Step verification to the
            team
          </span>
          <span class="toggler-btn">
            <SwitchField
              defaultChecked={!!second_factor_auth_enforced}
              onChange={(flag, cb) =>
                this.toggle2FA(flag).then(completed => {
                  completed && this.update2FAState(flag);
                  cb(completed);
                })
              }
              type="prime"
            />
            {!!second_factor_auth_enforced ? (
              <b class="text-primary">Enabled</b>
            ) : (
              <b className="text-faded">Disabled</b>
            )}
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
