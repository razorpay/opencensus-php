import React from 'react';
import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import { OtpInput } from 'common/new-ui/Input/OtpInput';
import { AsyncBtn } from 'common/new-ui/Button';

import { closeModal } from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

class TwoFactorVerificationOTP extends React.Component {
  static defaultProps = {
    onWrongOtp: () => {},
    onSuccess: () => {},
  };

  state = {};

  updateOtpValue = (otp) => {
    this.otpValue = otp;
  };

  onCloseClick = () => {
    this.props.onClose?.();
    this.props.closeModal();
  };

  onConfirm = () => {
    return this.validOtp()
      ? this.props
          .onConfirm({
            otp: this.otpValue,
            receiver: this.props.contactMobile,
          })
          .then((data) => {
            // not calling onCloseClick onSuccess since it triggers onClose callback
            // closeModal can be explicitly called in onSuccess callback if required
            this.props.onSuccess(data);
          })
          .catch(({ errors }) => {
            this.props.onWrongOtp({ errors });
            this.setState({ wrongOtp: true });
          })
      : this.setState({ wrongOtp: true });
  };

  validOtp = () => this.otpValue && this.otpValue.length === 6;

  componentDidMount() {
    analyticsTrack({
      objectName: '2fa setup popup',
      actionName: 'displayed',
      screen: 'my account',
      properties: {
        '2FaFlow': this.props.title,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    analyticsTrack({
      objectName: '2fa otp',
      actionName: 'sent',
      screen: 'my account',
      properties: {
        action: 'cancel',
        '2FaFlow': this.props.title,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  render() {
    return (
      <div>
        <ModalHeader
          title={this.props.title}
          onCloseClick={(...e) => {
            analyticsTrack({
              objectName: '2fa setup popup',
              actionName: 'clicked',
              screen: 'my account',
              properties: {
                action: 'cancel',
                '2FaFlow': this.props.title,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.onCloseClick(...e);
          }}
        />
        <div class="modal-body">
          {this.props.renderMessage()}
          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={this.state.wrongOtp}
          />
          <p class="m-t m-b">
            Didn’t receive an OTP?{' '}
            <AsyncBtn.Transparent
              pendingState="Sending OTP..."
              onClick={() => {
                analyticsTrack({
                  objectName: '2fa setup popup',
                  actionName: 'clicked',
                  screen: 'my account',
                  properties: {
                    action: 'resend',
                    '2FaFlow': this.props.title,
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                return this.props.onResend();
              }}
              class="m-l"
              showLoader={false}
            >
              Resend
            </AsyncBtn.Transparent>
          </p>
          <div class="Modal__actions">
            <AsyncBtn.Primary
              pendingState="Verifying OTP..."
              type="submit"
              class="Button--full-width"
              onClick={this.onConfirm}
            >
              Confirm
            </AsyncBtn.Primary>
          </div>
        </div>
      </div>
    );
  }
}

export default connect(null, { closeModal })(TwoFactorVerificationOTP);
