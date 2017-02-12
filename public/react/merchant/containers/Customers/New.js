import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import { required, email, phone } from 'rzp/utils/validators'
import * as CustomerActions from 'merchant/modules/customers'

@connect(
  null,
  CustomerActions
)
@reduxForm({
  form: 'newCustomer'
})
export default class AddCustomer extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
    this.save = ::this.save
  }

  componentWillMount() {
    if (this.props.customer) {
      this.props.initialize(this.props.customer)
    }
  }

  save(props) {
    return this.props.saveCustomer(props).then((customer) => {
      this.props.onSave(customer)
    }).catch((err) => {
      this.setState({
        errors: err.errors
      })
    })
  }

  render() {
    const { handleSubmit, pristine } = this.props

    return (
      <div>
        <ModalHeader
          title={this.props.customer ? 'Edit Customer' : 'New Customer'}
          onCloseClick={this.props.closeModal}
        />

        <div class='modal-body'>
          <Alert
            type='error'
            message={this.state.errors}
          />

          <form onSubmit={handleSubmit(this.save)}>
            <div class='form-group'>
              <label>Name</label>
              <div>
                <Field
                  name='name'
                  component={InputField}
                  class='form-control'
                  autoFocus={true}
                />
              </div>
            </div>

            <div class='form-group'>
              <label>Email</label>
              <div>
                <Field
                  name='email'
                  component={InputField}
                  type='email'
                  class='form-control'
                  validate={email('Please provide a valid email')}
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='label-required'>Contact No.</label>
              <div>
                <Field
                  name='contact'
                  component={InputField}
                  class='form-control'
                  type='tel'
                  validate={[
                    required('Please provide the contact number'),
                    phone('Invalid Contact')
                  ]}
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

            <div class='Modal__actions'>
              <AsyncButton
                type='submit'
                class='btn btn-primary btn-block'
                text={this.props.saveLabel}
                pendingText='Saving...'
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    )
  }
}

AddCustomer.defaultProps = {
  onSave: () => {},
  saveLabel: 'Save'
}
