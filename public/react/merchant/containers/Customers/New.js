import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import validator from 'rzp/utils/validator'
import * as CustomerActions from 'merchant/modules/customers'

@connect(
  null,
  CustomerActions
)
@reduxForm({
  form: 'newCustomer',
  validate: validator({
    email: {
      type: 'email',
      messages: {
        type: 'Email is invalid'
      }
    },
    contact: {
      presence: true
    }
  })
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
    const { handleSubmit } = this.props

    return (
      <div>
        <ModalHeader
          title={this.props.customer ? 'Edit Customer' : 'New Customer'}
          onCloseClick={this.props.closeModal}
        />

        <Alert
          type='error'
          message={this.state.errors}
        />

        <form class='form-horizontal'>
          <div class='modal-body'>
            <div class='form-group'>
              <label class='col-md-3 control-label'>Name</label>
              <div class='col-md-9'>
                <Field
                  name='name'
                  component={InputField}
                  class='form-control'
                  autoFocus={true}
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Email</label>
              <div class='col-md-9'>
                <Field
                  name='email'
                  component={InputField}
                  type='email'
                  class='form-control'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Contact No.</label>
              <div class='col-md-9'>
                <Field
                  name='contact'
                  component={InputField}
                  class='form-control'
                  type='tel'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Address</label>
              <div class='col-md-9'>
                <Field
                  name='address'
                  component='textarea'
                  class='form-control'
                />
              </div>
            </div>
          </div>

          <div class='modal-footer'>
            <button
              type='button'
              class='btn btn-default btn-rounded'
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type='button'
              class='btn btn-primary btn-rounded'
              text='Save'
              pendingText='Saving...'
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    )
  }
}

AddCustomer.defaultProps = {
  onSave: () => {}
}
