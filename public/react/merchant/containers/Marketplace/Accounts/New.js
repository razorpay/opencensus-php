import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { required } from 'rzp/utils/validators';
import * as AccountActions from 'merchant/modules/marketplace/accounts';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

@connect(null, {
  ...AccountActions,
  ...ModalActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'newAccount',
  initialValues: {
    account: true,
  },
})
export default class AddAccount extends Component {
  state = {
    errors: null,
  };

  save = props => {
    return this.props
      .saveAccount(props)
      .then(account => {
        this.props.onSave(account);
        this.props.showNotification({
          type: 'success',
          message: 'Account saved successfully',
        });
      })
      .catch(({ errors }) => {
        this.setState({
          errors: errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader title="Add Account" onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="label-required">Account Name</label>
              <div>
                <Field
                  name="name"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  validate={required()}
                />
                <small class="help-block">
                  The business/individual name for the account, which will appear on all reports
                </small>
              </div>
            </div>

            <div class="form-group">
              <label>Account Email</label>
              <div>
                <Field name="email" component="input" class="form-control" />
                <small class="help-block">
                  Optional - Contact email for the linked account. Razorpay will not communicate to this email directly.
                </small>
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Add"
                pendingText="Adding..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

AddAccount.defaultProps = {
  onSave: () => {},
};
