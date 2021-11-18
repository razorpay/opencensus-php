import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'common/ui/Forms/InputField';
import { showNotification } from 'merchant_common/reducers/notifications';
import { required } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { EMAIL_NOTIF } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
import { triggerOtpOnEmail } from 'merchant_common/reducers/twoFactor';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { updateEmailSettings, updateConfig } from 'merchant/reducers/config';
import rolesList from 'merchant/helpers/permissions/roles-list';
class EmailNotifications extends Component {
  constructor(props) {
    super(props);
    this.token = '';
    this.transaction_report_email = '';
  }

  state = {};

  componentDidMount() {
    this.props.initialize(this.props.config);
  }

  onEmailOtpConfirm = (data) => {
    const obj = {
      ...data,
      token: this.token,
      transaction_report_email: this.transaction_report_email,
    };

    return this.props.updateEmailSettings(obj).then((_) => {
      this.props.showNotification({
        type: 'success',
        message: 'Emails Updated',
        hidePrevious: true,
      });
    });
  };

  triggerVerificationOtp = () => {
    return triggerOtpOnEmail()
      .then(({ data }) => {
        this.token = data.token;
        this.triggerOtpModal();
      })
      .catch(({ err }) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  };

  triggerOtpModal() {
    this.props.openModal({
      size: 'small',
      component: (
        <TwoFactorVerificationOTP
          onSuccess={this.props.closeModal}
          onConfirm={this.onEmailOtpConfirm}
          onClose={this.props.closeModal}
          onResend={this.triggerVerificationOtp}
          title="OTP Verification"
          renderMessage={() => (
            <p className="m-b">
              The action you are trying to perform needs 2 step verification. An Email with 6- digit
              OTP has been sent to {this.props?.user?.user?.email}.
            </p>
          )}
        />
      ),
    });
  }

  handleUpdate = (data) => {
    if (this.props.org.features.indexOf('email_update_2fa_enabled') > -1) {
      return this.props
        .updateEmailSettings(data)
        .then((_) =>
          this.props.showNotification({
            type: 'success',
            message: 'Emails Updated',
            hidePrevious: true,
          }),
        )
        .catch((err) => {
          const error = err.errors;
          if (
            typeof error === 'object' &&
            !!error.internal_error_code &&
            error.internal_error_code === 'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED'
          ) {
            return this.triggerVerificationOtp();
          }
          return this.props.showNotification({
            type: 'error',
            message: err.errors[0],
          });
        });
    } else {
      return this.props
        .updateConfig(data)
        .then((_) =>
          this.props.showNotification({
            type: 'success',
            message: 'Emails Updated',
            hidePrevious: true,
          }),
        )
        .catch((err) => {
          this.props.showNotification({
            type: 'error',
            message: err.errors[0],
          });
        });
    }
  };

  handleSubmit = ({ transaction_report_email }) => {
    const emails = transaction_report_email ? transaction_report_email.split(',') : null;

    this.transaction_report_email = emails;

    return this.handleUpdate({
      transaction_report_email: emails,
    });
  };

  onSave = (e) => {
    this.analytics();
    return this.props.handleSubmit(this.handleSubmit)(e);
    // this.props.handleSubmit(this.props.onSave)(e);
  };

  analytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: 'Change - Email Notifications Addresses',
    });
    analyticsTrack({
      objectName: 'save email notifications',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  render() {
    return (
      <div>
        {this.props.user.role === rolesList.OWNER && (
          <div className="panel panel-default ftx-parent">
            <div className="panel-heading">
              <span className="title">
                <TextHighlighter hashedWith={EMAIL_NOTIF}>Email Notifications</TextHighlighter>
              </span>
            </div>

            <div className="panel-body">
              <form className="form-horizontal" onSubmit={this.onSave}>
                <div className="description">
                  Enter email addresses that will receive email notifications regarding payments,
                  settlements, daily payment reports, webhooks, etc. (You can enter multiple email
                  addresses separated by a comma.)
                </div>

                <div className="form-group">
                  <div className="col-sm-10">
                    <Field
                      name="transaction_report_email"
                      component={InputField}
                      className="form-control"
                      maxLength="255"
                      validate={required()}
                    />
                  </div>

                  <div className="col-sm-2">
                    <AsyncButton
                      className="btn btn-primary email-notification-cta"
                      text="Save Changes"
                      pendingText="Saving..."
                      onClick={this.onSave}
                    />
                  </div>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      config: state.config.config,
      org: state.session.org,
    }),
    { showNotification, updateEmailSettings, openModal, closeModal, updateConfig },
  ),
  reduxForm({}),
)(EmailNotifications);
