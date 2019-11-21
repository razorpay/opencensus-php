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

@connect(null, { updatePassword, closeModal, showNotification })
@RTracking(() => window.rzpQ.component('PasswordForm'))
@reduxForm({
  form: 'updatePasswordChangeForm',
})
export default class PasswordForm extends PureComponent {
  changePassword = props => {
    return this.props
      .updatePassword(props)
      .then(() => {
        const { showNotification, closeModal, tracking } = this.props;
        showNotification({
          type: 'success',
          message: 'Password changed successfully.',
        });
        tracking.trackEvent(
          window.rzpQ.onbr().initiated('dash.my_account_actions', {
            action: 'Change_Password_Successful',
          })
        );
        closeModal();
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;
    return (
      <form onSubmit={handleSubmit(this.changePassword)}>
        <ModalHeader
          title="Change Password"
          onCloseClick={this.props.closeModal}
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
