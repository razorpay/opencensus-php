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
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import ShowWhen from 'merchant/components/ShowWhen';

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    ...AccountActions,
    ...ModalActions,
    ...NotificationsActions,
  }
)
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

  componentWillMount() {
    const { accountData, user } = this.props;
    let email = null;
    //check whether the LA has its own email or not
    if (
      accountData &&
      user.merchants[user.current].email !== accountData.email
    ) {
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
    const { accountData } = this.props;
    let requestData = { ...props };
    let reqFunc = accountData ? this.props.updateEmail : this.props.saveAccount;

    if (accountData) {
      requestData.accountId = accountData.id;
      delete requestData.name;
    }

    return reqFunc(requestData)
      .then(account => {
        this.props.onSave(account);
        this.props.showNotification({
          type: 'success',
          message: accountData
            ? 'Email added successfully'
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
      <div class="accounts-create-new">
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
                  Your linked-account user can access their dashboard using this
                  email id. You may Add/Edit the email later.
                </small>
              </div>
            </div>

            {!accountData && (
              <ShowWhen myRole="owner admin manager">
                <div class="form-group">
                  <div class="rzpCheckbox">
                    <Field
                      name="dashboard_access"
                      id="dashboard_access"
                      component={CheckboxField}
                      type="checkbox"
                    />
                    <label for="dashboard_access" class="icon i-check">
                      <span>Enable Dashboard access to this account</span>
                    </label>
                  </div>
                </div>
              </ShowWhen>
            )}

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
