import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal } from 'rzp/modules/modals';

function validate(values) {
  let errors = {};
  let password = values.password || '';
  let password_confirmation = values.password_confirmation || '';

  if (!/\d/.test(values.password)) {
    errors.password = 'Password must contain atleast one Number.';
  }

  if (!/[a-zA-Z]/.test(values.password)) {
    errors.password = 'Password must contain atleast one alphabet.';
  }

  if (password.length < 7 || password.length > 50) {
    errors.password = 'Password must be between 7 and 50 characters.';
  }

  if (values.password !== values.password_confirmation) {
    errors.password_confirmation = 'The password confirmation does not match.';
  }

  return errors;
}

@connect(
  state => {
    return {
      ...state.session,
    };
  },
  { closeModal, ...NotificationsActions }
)
@reduxForm({
  form: 'createLogin',
  validate,
})
export default class CreateLogin extends Component {
  state = {
    errors: null,
  };

  componentWillMount() {
    let referral = this.props.referral;
    this.props.initialize({
      email: referral.email,
      id: referral.id,
      name: referral.name,
      password: '',
      password_confirmation: '',
    });
  }

  save = props => {
    return this.props
      .onSave(props)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: `Login created for the merchant ${props.name}`,
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, referral } = this.props;

    return (
      <div>
        <ModalHeader
          title="Create Login"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <p>
              Once you click OK, the merchant {referral.name} with id{' '}
              {referral.id} will be able to login to the Razorpay Dashboard
              using {referral.email} and the following password. The account
              will have complete access to the Merchant Account.
            </p>
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Password</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="password"
                  id="password"
                  component={InputField}
                  class="form-control"
                  type="password"
                  autoFocus={true}
                />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Confirm Password</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="password_confirmation"
                  id="password_confirmation"
                  component={InputField}
                  class="form-control"
                  type="password"
                />
              </div>
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
              class="btn btn-primary"
              text="Create Login"
              pendingText="Creating Login..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
