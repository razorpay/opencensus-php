import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import AsyncButton from 'react-async-button';
import rTracking from 'react-tracking';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { updatePassword } from 'merchant/reducers/profile';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { Modules } from 'common/constant/enums';

class PasswordForm extends PureComponent {
  passwordRegex = /^(?=.*\d)(?=.*[a-zA-Z]).{8,}$/;

  changePassword = (props) => {
    let errorMessage;
    if (props.old_password === props.password) {
      errorMessage = 'Old password cannot be the same as the new password';
    } else if (props.password !== props.password_confirmation) {
      errorMessage = 'New password and new password confirmation should be same';
    } else if (this.passwordRegex.test(props.password) !== true) {
      errorMessage =
        'The password must be between 8 and 50 characters, must include at least 1 letter and 1 number';
    }

    if (errorMessage) {
      this.props.showNotification({
        type: 'error',
        message: errorMessage,
      });

      return;
    }

    // eslint-disable-next-line consistent-return
    return this.props
      .updatePassword(props)
      .then(() => {
        const { user } = this.props;
        selfServeTrackSuccess({
          selfServeAction: 'Password Updated',
          page: user.isAccountAndSettingsRevampEnabled ? 'Personal Profile' : 'Profile',
          screen: user.isAccountAndSettingsRevampEnabled
            ? Modules.AccountAndSettings
            : Modules.MyAccount,
        });
        analyticsTrackWithUserInfo({
          objectName: 'change password',
          actionName: 'status',
          screen: Modules.AccountAndSettings,
          properties: {
            status: 'success',
          },
        });
        const { showNotification, closeModal, tracking } = this.props;
        showNotification({
          type: 'success',
          message: 'Password changed successfully.',
        });
        tracking.trackEvent(
          window.rzpQ.onbr().initiated('dash.my_account_actions', {
            action: 'Change_Password_Successful',
          }),
        );
        closeModal();
      })
      .catch((err) => {
        analyticsTrackWithUserInfo({
          objectName: 'change password',
          actionName: 'status',
          screen: Modules.AccountAndSettings,
          properties: {
            status: 'failure',
            failureReason: err.errors[0],
          },
        });
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;
    return (
      <form
        onSubmit={(args) => {
          analyticsTrackWithUserInfo({
            objectName: 'change password popup',
            actionName: 'clicked',
            screen: Modules.AccountAndSettings,
            properties: {
              location: 'profile',
              action: 'change password',
            },
          });
          return handleSubmit(this.changePassword)(...args);
        }}
      >
        <ModalHeader
          title="Change Password"
          onCloseClick={() => {
            analyticsTrackWithUserInfo({
              objectName: 'change password popup',
              actionName: 'clicked',
              screen: Modules.AccountAndSettings,
              properties: {
                location: 'profile',
                action: 'cancel',
              },
            });
            this.props.closeModal();
          }}
        />
        <div className="modal-body">
          <div className="form-group">
            <Field
              component={InputField}
              type="password"
              placeholder="Current Password"
              name="old_password"
              className="form-control"
              validate={required()}
              autoFocus={true}
            />
          </div>
          <div className="form-group">
            <Field
              component={InputField}
              type="password"
              placeholder="New Password"
              name="password"
              className="form-control"
              validate={required()}
            />
          </div>
          <div className="form-group">
            <Field
              component={InputField}
              type="password"
              placeholder="Confirm New Password"
              name="password_confirmation"
              className="form-control"
              validate={required()}
            />
          </div>

          <div className="Modal__actions">
            <AsyncButton
              type="submit"
              className="btn btn-primary btn-block"
              text="Change Password"
              pendingText="Updating..."
              onClick={handleSubmit(this.changePassword)}
            />
          </div>
        </div>
      </form>
    );
  }
}

export default compose(
  connect((state) => ({ user: state.session.user }), {
    updatePassword,
    closeModal,
    showNotification,
  }),
  rTracking(() => window.rzpQ.component('PasswordForm')),
  reduxForm({
    form: 'updatePasswordChangeForm',
  }),
)(PasswordForm);
