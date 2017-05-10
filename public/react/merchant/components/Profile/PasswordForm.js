import React, { PureComponent } from 'react';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';

@reduxForm({
  form: 'updatePasswordChangeForm',
})
export default class PasswordForm extends PureComponent {
  render() {
    const { handleSubmit, changePassword } = this.props;
    return (
      <form onSubmit={handleSubmit(changePassword)}>
        <div class="modal-header">
          <h3 class="modal-title">Change Password</h3>
        </div>
        <div class="modal-body change-pwd-modal">
          <div class="form-group">
            <Field
              component="input"
              type="password"
              placeholder="Current Password"
              name="old_password"
              class="form-control"
            />
          </div>
          <div class="form-group">
            <Field
              component="input"
              type="password"
              placeholder="New Password"
              name="password"
              class="form-control"
            />
          </div>
          <div class="form-group">
            <Field
              component="input"
              type="password"
              placeholder="Confirm New Password"
              name="password_confirmation"
              class="form-control"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-default"
            onClick={this.props.closeModal}
          >
            Cancel
          </button>
          <AsyncButton
            type="submit"
            class="btn btn-primary modal-ok"
            text="Change Password"
            pendingText="Updating..."
            onClick={handleSubmit(this.props.changePassword)}
          />
        </div>
      </form>
    );
  }
}
