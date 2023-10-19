import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';
import { compose } from 'redux';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  triggerOtpOnEmail,
  triggerOtpOnSMS,
  triggerOtpOnBoth,
  verifyOtpOnEmail,
  verifyOtpOnSMS,
  verifyOtpOnBoth,
  verifyTwoFactorOtp,
  verifyTwoFactorOtpMobile,
} from 'merchant_common/reducers/twoFactor';
import { updateContactMobile, updateUser } from 'merchant_common/reducers/user';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import EditContactMobileForm from './EditContactMobileForm';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { Modules } from 'common/constant/enums';

// eslint-disable-next-line react/no-unsafe
class UpdateContactMobile extends React.Component {
  state = {};

  constructor(props) {
    super();
    this.contactMobile = props.contactMobile;
  }

  UNSAFE_componentWillMount() {
    this.triggerVerificationOtp();
  }

  getOTPDestination = () => {
    const { user } = this.props.user;
    const hasOnlyEmail = Boolean(user.email && user.confirmed);
    const hasOnlyMobile = Boolean(user.contact_mobile && user.contact_mobile_verified);
    const hasBothEmailAndMobile = hasOnlyEmail && hasOnlyMobile;

    return {
      hasOnlyEmail,
      hasOnlyMobile,
      hasBothEmailAndMobile,
    };
  };

  @RTracking((props) => {
    return props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('change_contact_mobile'),
    );
  })
  onContactMobileUpdateComplete = (data) => {
    // Passing contact_mobile_verified hardcoded as true in callback
    // Ideally this should come from API, but BE is unable send that as response
    // in current state
    const { user } = this.props;
    const page = this.getPageOpenedOn();
    if (page) {
      selfServeTrackSuccess({
        selfServeAction: 'Mobile Updated',
        page,
        screen: user.isAccountAndSettingsRevampEnabled
          ? Modules.AccountAndSettings
          : Modules.MyAccount,
      });
    }
    this.props.updateUser({ contact_mobile: data.data.contact_mobile });
    return this.props.onComplete({ contact_mobile_verified: true });
  };

  getPageOpenedOn = () => {
    const { pathname } = this.props?.location;
    switch (pathname) {
      case '/account-settings':
        return 'Personal Profile';
      case '/profile':
        return 'Profile';
      case '/business-settings/contact':
        return 'Contact details';
      default:
        return null;
    }
  };

  onContactMobileVerificationOtpConfirm = (data) => {
    return this.props.verifyTwoFactorOtpMobile(data);
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
      contactMobile={this.contactMobile}
      title="Verify your mobile number"
      renderMessage={() => (
        <>
          <p class="m-b">An SMS with 6-digit OTP has been sent to {this.contactMobile}</p>
          <p class="m-t m-b">OTP will expire in 5 mins.</p>
        </>
      )}
      isNewAccountAndSettingsPage={this.props.isNewAccountAndSettingsPage}
    />
  );

  onContactMobileUpdate = (newContactMobile) => {
    this.contactMobile = newContactMobile;
    this.props.closeModal();
    this.props.openModal({
      size: 'small',
      component: this.getContactMobileTwoFactorVerificationUI(),
    });
  };

  onEmailOtpVerificationComplete = () => {
    const { contactMobile, isNewAccountAndSettingsPage, closeModal, openModal } = this.props;
    closeModal();
    openModal({
      size: 'small',
      isNew: true,
      component: (
        <EditContactMobileForm
          onContactMobileUpdate={this.onContactMobileUpdate}
          otpAuthToken={this.state.otpAuthToken}
          contactMobile={contactMobile}
          onClose={this.onCloseClick}
          isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
        />
      ),
    });
  };

  triggerVerificationOtp = (resend = {}) => {
    analyticsTrackWithUserInfo({
      objectName: '2fa email otp',
      actionName: 'sent',
      screen: this.props.isNewAccountAndSettingsPage
        ? Modules.AccountAndSettings
        : Modules.MyAccount,
      properties: {
        resend,
      },
    });

    const { hasBothEmailAndMobile, hasOnlyEmail } = this.getOTPDestination();

    const triggerOTPFn = hasBothEmailAndMobile
      ? triggerOtpOnBoth
      : hasOnlyEmail
      ? triggerOtpOnEmail
      : triggerOtpOnSMS;

    return triggerOTPFn()
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
    const { hasBothEmailAndMobile, hasOnlyEmail } = this.getOTPDestination();

    const verifyOTPFn = hasBothEmailAndMobile
      ? verifyOtpOnBoth
      : hasOnlyEmail
      ? verifyOtpOnEmail
      : verifyOtpOnSMS;

    return verifyOTPFn({
      ...data,
      token: this.state.token,
    }).then(({ data: dataCurrent }) => {
      this.setState({
        otpAuthToken: dataCurrent.otp_auth_token,
      });
    });
  };

  render() {
    const { user } = this.props.user;
    const { hasBothEmailAndMobile, hasOnlyEmail, hasOnlyMobile } = this.getOTPDestination();

    return (
      <TwoFactorVerificationOTP
        onSuccess={this.onEmailOtpVerificationComplete}
        onConfirm={this.onEmailOtpConfirm}
        onClose={this.onCloseClick}
        onWrongOtp={this.onWrongOtp}
        onResend={this.triggerVerificationOtp}
        title="Change mobile number"
        renderMessage={() => (
          <p class="m-b">
            Changing mobile number requires you to enter OTP sent over to your{' '}
            {hasOnlyEmail && (
              <>
                registered email address <strong>{this.props.userEmail}</strong>
              </>
            )}
            {hasBothEmailAndMobile && ' and '}
            {hasOnlyMobile && (
              <>
                registered phone number <strong>{user.contact_mobile}</strong>
              </>
            )}
          </p>
        )}
        isNewAccountAndSettingsPage={this.props.isNewAccountAndSettingsPage}
      />
    );
  }

  onCloseClick = () => {
    this.props.onClose?.();
    this.props.closeModal();
  };

  onWrongOtp = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().failed('changed_contact_mobile.wrong_otp'),
    );
  };
}

const mapStateToProps = (state) => ({
  contactMobile: (state.session.user.user || {}).contact_mobile,
  userEmail: (state.session.user.user || {}).email,
  user: state.session.user,
});

export default compose(
  withRouter,
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('UpdateContactMobile')),
  connect(mapStateToProps, {
    closeModal,
    openModal,
    showNotification,
    verifyTwoFactorOtp,
    verifyTwoFactorOtpMobile,
    updateContactMobile,
    updateUser,
  }),
)(UpdateContactMobile);
