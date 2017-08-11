import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { required, email, phone } from 'rzp/utils/validators';
import * as CustomerActions from 'merchant/modules/customers';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

function validate(values) {
  let errors = {};

  if (!values.email && !values.contact) {
    errors._error = 'Please provide either email or contact';
  }

  return errors;
}

@connect(null, {
  ...CustomerActions,
  ...ModalActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'newCustomer',
  validate,
})
export default class AddCustomer extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentWillMount() {
    if (this.props.customer) {
      this.props.initialize(this.props.customer);
    }
  }

  save = props => {
    return this.props
      .saveCustomer(props)
      .then(customer => {
        this.props.onSave(customer);
        this.props.showNotification({
          type: 'success',
          message: 'Customer saved successfully',
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, invalid, customer } = this.props;

    return (
      <div>
        <ModalHeader
          title={customer && customer.id ? 'Edit Customer' : 'New Customer'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label>Name</label>
              <div>
                <Field
                  name="name"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                />
              </div>
            </div>

            <div class="help-block">
              Either <b>email</b> or <b>Contact No.</b> is mandatory.
            </div>

            <div class="form-group">
              <label>Email</label>
              <div>
                <Field
                  name="email"
                  component={InputField}
                  type="email"
                  class="form-control"
                  validate={email('Please provide a valid email')}
                />
              </div>
            </div>

            <div class="form-group">
              <label>Contact No.</label>
              <div>
                <Field
                  name="contact"
                  component={InputField}
                  class="form-control"
                  type="tel"
                  validate={[phone('Invalid Contact')]}
                />
              </div>
            </div>

            {/*
            <div class='form-group'>
              <label>Address</label>
              <div>
                <Field
                  name='address'
                  component='textarea'
                  class='form-control'
                />
              </div>
            </div>
*/}

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block"
                text={this.props.saveLabel}
                pendingText="Saving..."
                disabled={invalid}
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

AddCustomer.defaultProps = {
  onSave: () => {},
  saveLabel: 'Save',
};
