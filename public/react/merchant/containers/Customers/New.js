import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import { isBlank } from 'rzp/utils/rzp-utils'
import { createCustomer, customerAdded } from 'merchant/modules/customers'

const validate = values => {
  const errors = {}
  if (isBlank(values.email)) {
    errors.email = 'Required'
  }

  if (isBlank(values.contact)) {
    errors.contact = 'Required'
  }
  return errors
}

@connect(
  null,
  { createCustomer, customerAdded }
)
@reduxForm({
  form: 'newCustomer',
  validate
})
export default class AddCustomer extends Component {
  constructor() {
    super(...arguments)
    this.save = ::this.save
  }

  save(fieldProps) {
    return this.props.createCustomer(fieldProps).then((response) => {
      let customer = response.data.customer
      this.props.customerAdded(customer)
      this.props.onSave(customer)
    })
  }

  render() {
    const { handleSubmit } = this.props
    return (
      <div>
        <ModalHeader
          title='Add Customer'
          onCloseClick={this.props.closeModal}
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
          </div>

          <div class='modal-footer'>
            <button
              type='button'
              class='btn btn-default'
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type='button'
              class='btn btn-primary'
              text='Save'
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    )
  }
}
