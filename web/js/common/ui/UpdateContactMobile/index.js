import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  triggerOtpOnEmail,
  verifyOtpOnEmail,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';
import { updateContactMobile } from 'merchant_common/reducers/user';

import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';

import EditContactMobileForm from './EditContactMobileForm';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

@connect(
  (state) => ({
    contactMobile: (state.session.user.user || {}).contact_mobile,
    userEmail: (state.session.user.user || {}).email,
  }),
  {
    closeModal,
    openModal,
    showNotification,
    verifyTwoFactorOtp,
    updateContactMobile,
  },
)
@RTracking(() => window.rzpQ.component('UpdateContactMobile'))
export default class UpdateContactMobile extends React.Component {
  state = {};

  constructor(props) {
    super();
    this.contactMobile = props.contactMobile;
  }

  componentWillMount() {
    this.triggerEmailVerificationOtp();
  }

  @RTracking((props) => {
    return props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('change_contact_mobile'),
    );
  })
  onContactMobileUpdateComplete = () => {
    // Passing contact_mobile_verified hardcoded as true in callback
    // Ideally this should come from API, but BE is unable send that as response
    // in current state
    return this.props.onComplete({ contact_mobile_verified: true });
  };

  onContactMobileVerificationOtpConfirm = (data) => {
    return this.props.verifyTwoFactorOtp(data);
  };

  onMobileVerificationOtpResend = () => {
    return this.props.updateContactMobile({
      contact_mobile: this.contactMobile,
      otp_auth_token: this.state.otpAuthToken,
    });
  };

  getContactMobileTwoFactorVerificationUI = () => (
    <TwoFactorVerificationOTP
      onSuccess={this.onContactMobileUpdateComplete}
      onClose={this.onCloseClick}
      onConfirm={this.onContactMobileVerificationOtpConfirm}
      onWrongOtp={this.onWrongOtp}
      onResend={this.onMobileVerificationOtpResend}
      title="Verify your mobile number"
      renderMessage={() => (
        <>
          <p class="m-b">An SMS with 6-digit OTP has been sent to {this.state.contactMobile}</p>
          <p class="m-t m-b">OTP will expire in 5 mins.</p>
        </>
      )}
    />
  );

  onContactMobileUpdate = (newContactMobile) => {
    this.contactMobile = newContactMobile;
    this.props.openModal({
      size: 'small',
      component: this.getContactMobileTwoFactorVerificationUI(),
    });
  };

  onEmailOtpVerificationComplete = () => {
    this.props.closeModal();
    this.props.openModal({
      size: 'small',
      component: (
        <EditContactMobileForm
          onContactMobileUpdate={this.onContactMobileUpdate}
          otpAuthToken={this.state.otpAuthToken}
          contactMobile={this.props.contactMobile}
          onClose={this.onCloseClick}
        />
      ),
    });
  };

  triggerEmailVerificationOtp = (resend = {}) => {
    analyticsTrack({
      objectName: '2fa email otp',
      actionName: 'sent',
      screen: 'my account',
      properties: {
        resend: resend,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    return triggerOtpOnEmail()
      .then(({ data }) => {
        this.setState({
          token: data.token,
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  onEmailOtpConfirm = (data) => {
    return verifyOtpOnEmail({
      ...data,
      token: this.state.token,
    }).then(({ data }) => {
      this.setState({
        otpAuthToken: data.otp_auth_token,
      });
    });
  };

  render() {
    return (
      <TwoFactorVerificationOTP
        onSuccess={this.onEmailOtpVerificationComplete}
        onConfirm={this.onEmailOtpConfirm}
        onClose={this.onCloseClick}
        onWrongOtp={this.onWrongOtp}
        onResend={this.triggerEmailVerificationOtp}
        title="Change mobile number"
        renderMessage={() => (
          <p class="m-b">
            Changing mobile number requires you to enter OTP sent over your registered email address{' '}
            <strong>{this.props.userEmail}</strong>
          </p>
        )}
      />
    );
  }

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  onWrongOtp = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().failed('changed_contact_mobile.wrong_otp'),
    );
  };
}
