import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'rzp/utils/validators';
import InputField from 'rzp/ui/Forms/InputField';
import { changePassword } from 'merchant/modules/profile';
import { closeModal } from 'rzp/modules/modals';

@connect(null, { changePassword, closeModal })
@reduxForm({
  form: 'updatePasswordChangeForm',
})
export default class PasswordForm extends PureComponent {
  changePassword = props => {
    return this.props
      .updatePassword(props)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Password changed successfully.',
        });
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
