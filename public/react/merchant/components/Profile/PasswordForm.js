import React, { PureComponent } from 'react';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';

@reduxForm({
  form: 'password',
})
export default class PasswordForm extends PureComponent {
  render() {
    const { handleSubmit, changePassword } = this.props;
    return (
      <form onSubmit={handleSubmit(changePassword)}>
        <div className="modal-header">
          <h3 className="modal-title">Change Password</h3>
        </div>
        <div className="modal-body change-pwd-modal">
          <div className="form-group">
            <Field
              component="input"
              type="password"
              placeholder="Current Password"
              name="old_password"
              class="form-control"
            />
          </div>
          <div className="form-group">
            <Field
              component="input"
              type="password"
              placeholder="New Password"
              name="password"
              class="form-control"
            />
          </div>
          <div className="form-group">
            <Field
              component="input"
              type="password"
              placeholder="Confirm New Password"
              name="password_confirmation"
              class="form-control"
            />
          </div>
        </div>
        <div className="modal-footer">
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
