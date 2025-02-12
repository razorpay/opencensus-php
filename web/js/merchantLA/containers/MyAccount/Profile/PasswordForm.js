import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { updatePassword } from 'merchantLA/reducers/profile';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { compose } from 'redux';

class PasswordForm extends PureComponent {
  changePassword = (props) => {
    return updatePassword(props)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Password changed successfully.',
        });
        analyticsTrack({
          objectName: 'change password',
          actionName: 'status',
          screen: 'my account',
          properties: {
            location: 'profile',
            success: true,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.closeModal();
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'change password',
          actionName: 'status',
          screen: 'my account',
          properties: {
            location: 'profile',
            success: false,
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
      <form onSubmit={handleSubmit(this.changePassword)}>
        <ModalHeader title="Change Password" onCloseClick={this.props.closeModal} />
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
  connect(null, { closeModal, showNotification }),
  reduxForm({
    form: 'updatePasswordChangeForm',
  }),
)(PasswordForm);
