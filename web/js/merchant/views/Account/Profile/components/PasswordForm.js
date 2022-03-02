import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import RTracking from 'react-tracking';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { updatePassword } from 'merchant/reducers/profile';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(null, { updatePassword, closeModal, showNotification })
@RTracking(() => window.rzpQ.component('PasswordForm'))
@reduxForm({
  form: 'updatePasswordChangeForm',
})
export default class PasswordForm extends PureComponent {
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
        analyticsTrack({
          objectName: 'change password',
          actionName: 'status',
          screen: 'my account',
          properties: {
            status: 'success',
            ...getCommonAnalyticsProperties(window.rzp_user),
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
        analyticsTrack({
          objectName: 'change password',
          actionName: 'status',
          screen: 'my account',
          properties: {
            status: 'failure',
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
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
          analyticsTrack({
            objectName: 'change password popup',
            actionName: 'clicked',
            screen: 'my account',
            properties: {
              location: 'profile',
              action: 'change password',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          return handleSubmit(this.changePassword)(...args);
        }}
      >
        <ModalHeader
          title="Change Password"
          onCloseClick={() => {
            analyticsTrack({
              objectName: 'change password popup',
              actionName: 'clicked',
              screen: 'my account',
              properties: {
                location: 'profile',
                action: 'cancel',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
          }}
        />
        <div class="modal-body">
          <div class="form-group">
            <Field
              component={InputField}
              type="password"
              placeholder="Current Password"
              name="old_password"
              class="form-control"
              validate={required()}
              autoFocus={true}
            />
          </div>
          <div class="form-group">
            <Field
              component={InputField}
              type="password"
              placeholder="New Password"
              name="password"
              class="form-control"
              validate={required()}
            />
          </div>
          <div class="form-group">
            <Field
              component={InputField}
              type="password"
              placeholder="Confirm New Password"
              name="password_confirmation"
              class="form-control"
              validate={required()}
            />
          </div>

          <div class="Modal__actions">
            <AsyncButton
              type="submit"
              class="btn btn-primary btn-block"
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
