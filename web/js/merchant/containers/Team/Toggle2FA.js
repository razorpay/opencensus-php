import { Component } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import { toggle2FaEnforcement } from 'merchant/modules/team';

import { openModal, closeModal } from 'rzp/modules/modals';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import {
  VerifyOtp,
  MissingNumbers,
  AskMobileNumber,
  EnableAgreement,
  DisableAgreement,
  PasswordVerification,
} from 'merchant/containers/Team/TwoFAModals';

@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  {
    updateFeatures,
    showNotification,
    openModal,
    closeModal,
    toggle2FaEnforcement,
  }
)
export default class Toggle2FA extends Component {
  // constructor(props) {
  //   super(props);
  //   this.state = this.props.state

  // }

  componentWillReceiveProps(nextProps) {
    // if (!this.props.features.length && nextProps.features.length) {
    //   const twoFAEnabled = this.getToggle2FAFlag(nextProps.features);
    //   this.setState({ twoFAEnabled });
    // }
  }

  getToggle2FAFlag(features) {
    // let noToggle2FA =
    //   features.find(feature => feature.feature === 'noToggle2FA') || {};
    // const twoFAEnabled = !noToggle2FA.value;
    // return twoFAEnabled;
  }

  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Flash Checkout`,
    });
  };
  showModal = component => {
    //Close anyother open modal
    this.props.closeModal();
    this.props.openModal({
      size: 'small',
      component: component,
    });
  };
  toggle2FA = flag => {
    //Step
    const verifyPassword = () => {
      const title = (flag ? 'Enable' : 'Disable') + ' 2-step verification';
      this.showModal(
        <PasswordVerification
          title={title}
          {...this.props}
          email={this.props.user.email}
          onConfirm={password =>
            this.props.toggle2FaEnforcement(flag, password)
          }
        />
      );
    };
    const otpVerification = mobile => {
      this.showModal(
        <VerifyOtp
          {...this.props}
          mobile={mobile}
          onConfirm={otp => {
            //Verify Otp here
            //On Success
            verifyPassword();
            //On failure
            // otpVerification(false)
          }}
        />
      );
    };
    const verifyMobile = () => {
      this.showModal(
        <AskMobileNumber
          {...this.props}
          onComplete={mobile => otpVerification(mobile)}
        />
      );
    };
    if (flag) {
      //Step 1: check of user has mobile number verified for setup to continue, if yes ask for password after agreement
      let {
        user: { second_factor_auth },
      } = this.props.user;
      if (undefined !== second_factor_auth && second_factor_auth) {
        this.showModal(
          <EnableAgreement {...this.props} onAgree={verifyPassword} />
        );
      } else {
        this.showModal(
          <EnableAgreement {...this.props} onAgree={verifyMobile} />
        );
      }
      // //Step 2: Make initial check if everyone in team has mobile number, if not show missing mobile number
      // if (false)
      //   this.showModal(
      //     <MissingNumbers {...this.props} onAgree={verifyPassword} />
      //   );
      //Step 2: Show an agreement to enable the 2FA & verify Password after agreement
      // this.showModal(
      //   <EnableAgreement {...this.props} onAgree={verifyPassword} />
      // );
      //Step 3:Once Password is confimred, check if second_factor_auth_setup is true
      if (false)
        this.showModal(
          <AskMobileNumber {...this.props} onComplete={() => VerifyOtp} />
        );
    } else {
      //Step 1: Show an agreement to enable the 2FA & verify Password after agreement
      this.showModal(
        <DisableAgreement {...this.props} onAgree={verifyPassword} />
      );
    }
  };

  render() {
    let {
      user: { second_factor_auth: twoFAEnabled },
    } = this.props.user;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">
            <i class="i i-phonelink-lock"></i> &nbsp; 2-Step verification to the
            team
          </span>
          <span class="toggler-btn">
            <SwitchField
              defaultChecked={!!twoFAEnabled}
              onChange={flag => this.toggle2FA(flag)}
              type="prime"
            />
            {true ? (
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
