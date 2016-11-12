import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import { isBlank } from 'rzp/utils/rzp-utils'
import * as CustomerActions from 'merchant/modules/customers'

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

const selector = formValueSelector('newCustomer')
@connect(
  (state) => {
    let isNew = !selector(state, 'id')
    return {
      isNew
    }
  },
  CustomerActions
)
@reduxForm({
  form: 'newCustomer',
  validate
})
export default class AddCustomer extends Component {
  constructor() {
    super(...arguments)
    this.create = ::this.create
    this.edit = ::this.edit
  }

  create(fieldProps) {
    return this.props.createCustomer(fieldProps).then((response) => {
      let customer = response.data.customer
      this.props.customerAdded(customer)
      this.props.onSave(customer)
    })
  }

  edit(fieldProps) {
    let { id, ...params } = fieldProps
    return this.props.editCustomer(id, params).then((response) => {
      let customer = response.data.customer
      this.props.customerEdited(customer)
      this.props.onSave(customer)
    })
  }

  render() {
    const { handleSubmit, isNew } = this.props
    let action = isNew ? this.create : this.edit

    return (
      <div>
        <ModalHeader
          title={isNew ? 'New Customer' : 'Edit Customer'}
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
              onClick={handleSubmit(action)}
            />
          </div>
        </form>
      </div>
    )
  }
}
