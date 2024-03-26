import React from 'react';
import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import { OtpInput } from 'common/new-ui/Input/OtpInput';
import { AsyncBtn } from 'common/new-ui/Button';

import { closeModal } from 'merchant_common/reducers/modals';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';

class TwoFactorVerificationOTP extends React.Component {
  static defaultProps = {
    onWrongOtp: () => {},
    onSuccess: () => {},
  };

  noOfAttempts = 1;
  timerIntervalId = null;

  state = { otpValue: null, resendTimer: 30, wrongOtp: false, disableConfirmButton: false };

  setTimerInterval = () => {
    this.timerIntervalId = setInterval(() => {
      this.setState((state) => {
        if (state.resendTimer > 0) {
          return { resendTimer: state.resendTimer - 1 };
        } else {
          clearInterval(this.timerIntervalId);
        }
        return {};
      });
    }, 1000);
  };

  componentWillUnmount() {
    this.timerIntervalId && clearInterval(this.timerIntervalId);
  }

  updateOtpValue = (otp) => {
    this.setState({ otpValue: otp });
  };

  onCloseClick = () => {
    this.props.onClose?.();
    this.props.closeModal();
  };

  onConfirm = () => {
    return this.validOtp()
      ? (this.setState({ disableConfirmButton: true }),
        this.props
          .onConfirm({
            otp: this.state.otpValue,
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
          .finally(() => {
            this.setState({ disableConfirmButton: false });
          }))
      : this.setState({ wrongOtp: true });
  };

  validOtp = () => this.state.otpValue && this.state.otpValue.length === 6;

  onAnalyticsTrack = ({ objectName, actionName, properties = {} }) => {
    const { isNewAccountAndSettingsPage, title } = this.props;
    analyticsTrackWithUserInfo({
      objectName,
      actionName,
      screen: isNewAccountAndSettingsPage ? Modules.AccountAndSettings : Modules.MyAccount,
      properties: {
        '2FaFlow': title,
        ...properties,
      },
    });
  };

  componentDidMount() {
    this.onAnalyticsTrack({
      objectName: '2fa setup popup',
      actionName: 'displayed',
    });
    this.onAnalyticsTrack({
      objectName: '2fa otp',
      actionName: 'sent',
      properties: {
        action: 'cancel',
      },
    });
    this.setTimerInterval();
  }

  attemptsTimerRef = { 2: 60, 3: 120, 4: 240, 5: 300 };
  onResendOtp = () => {
    const { onResend } = this.props;

    this.noOfAttempts += 1;
    this.onAnalyticsTrack({
      objectName: '2fa setup popup',
      actionName: 'clicked',
      properties: {
        action: 'resend',
        otpAttempts: this.noOfAttempts,
      },
    });

    return onResend().then(() => {
      this.setState(
        { resendTimer: this.attemptsTimerRef[this.noOfAttempts] || this.attemptsTimerRef[5] },
        this.setTimerInterval,
      );
    });
  };

  render() {
    const { resendTimer, wrongOtp, disableConfirmButton } = this.state;
    const { title, renderMessage } = this.props;
    return (
      <div>
        <ModalHeader
          title={title}
          onCloseClick={(...e) => {
            this.onAnalyticsTrack({
              objectName: '2fa setup popup',
              actionName: 'clicked',
              properties: {
                action: 'cancel',
              },
            });
            this.onCloseClick(...e);
          }}
        />
        <div className="modal-body">
          {renderMessage()}
          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={wrongOtp}
          />
          <p className="m-t m-b">
            {resendTimer > 0 ? (
              `Resend OTP in ${resendTimer} seconds`
            ) : (
              <>
                Didn’t receive an OTP?{' '}
                <AsyncBtn.Transparent
                  pendingState="Sending OTP..."
                  onClick={this.onResendOtp}
                  className="m-l"
                  showLoader={false}
                >
                  Resend
                </AsyncBtn.Transparent>
              </>
            )}
          </p>
          <div className="Modal__actions">
            <AsyncBtn.Primary
              pendingState="Verifying OTP..."
              type="submit"
              className="Button--full-width"
              onClick={(...e) => {
                this.onAnalyticsTrack({
                  objectName: '2fa setup popup',
                  actionName: 'clicked',
                  properties: {
                    action: 'confirm',
                  },
                });
                this.onConfirm(...e);
              }}
              disabled={disableConfirmButton}
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
