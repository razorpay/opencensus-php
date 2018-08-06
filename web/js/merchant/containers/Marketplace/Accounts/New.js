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
import store from 'merchant/store';
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

  isEmailEditable = false;

  componentWillMount() {
    const accountData = this.props.accountData;
    const user = store.getState().session.user;
    let email = null;
    //check whether the LA has its own email or not
    if (
      accountData &&
      user.merchants[user.current].email !== accountData.email
    ) {
      this.isEmailEditable = true;
      email = accountData.email;
    }

    if (accountData) {
      this.props.initialize({
        name: accountData.name,
        ...(email && { email: accountData.email }),
      });
    }
  }

  save = props => {
    let requestData = { ...props };
    let reqFunc = this.isEmailEditable
      ? this.props.updateEmail
      : this.props.saveAccount;

    if (this.isEmailEditable) {
      requestData['accountId'] = this.props.accountData.id;
      delete requestData.name;
    }

    return reqFunc(requestData)
      .then(account => {
        this.props.onSave(account);
        this.props.showNotification({
          type: 'success',
          message: this.isEmailEditable
            ? 'Email edited successfully'
            : 'Account created successfully',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.setState({
          errors: errors,
        });
      });
  };

  render() {
    const { handleSubmit, accountData } = this.props;

    return (
      <div>
        <ModalHeader
          title={!!accountData ? 'Edit Account' : 'Add Account'}
          onCloseClick={this.props.closeModal}
        />

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
                  disabled={!!accountData}
                />
                <small class="help-block">
                  The business/individual name for the account, which will
                  appear on all reports
                </small>
              </div>
            </div>

            <div class="form-group">
              <label>Account Email</label>
              <div>
                <Field name="email" component="input" class="form-control" />
                <small class="help-block">
                  Your sub-merchant will receive a Razorpay sign-up link on this
                  email.
                </small>

                <small class="help-block">
                  Note: If no email is provided, your email will be set as the
                  registered email ID of this merchant. You can add email ID
                  later.
                </small>
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text={!!accountData ? 'Update' : 'Add'}
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
