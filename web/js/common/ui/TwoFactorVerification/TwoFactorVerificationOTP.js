import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import OtpInput from 'common/new-ui/Input/OtpInput';
import { AsyncBtn } from 'common/new-ui/Button';

import { closeModal } from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(null, { closeModal })
export default class TwoFactorVerificationOTP extends React.Component {
  static defaultProps = {
    onWrongOtp: () => {},
    onSuccess: () => {},
  };

  state = {};

  updateOtpValue = (otp) => {
    this.otpValue = otp;
  };

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  onConfirm = () => {
    return this.props
      .onConfirm({
        otp: this.otpValue,
      })
      .then(() => {
        this.onCloseClick();
        this.props.onSuccess();
      })
      .catch(({ errors }) => {
        this.props.onWrongOtp({ errors });
        this.setState({ wrongOtp: true });
      });
  };

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
              onClick={(...e) => {
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
                this.props.onResend(...e);
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
