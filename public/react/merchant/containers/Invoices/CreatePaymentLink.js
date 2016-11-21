import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import { isBlank } from 'rzp/utils/rzp-utils'
import { createInvoice, appendInvoiceToList } from 'merchant/modules/invoices/list'

function validate(values) {
  let errors = {}
  let customer = values.customer
  let item = values.line_items ? values.line_items[0] : null
  let lineItemError = {}

  if (isBlank(customer) || (isBlank(customer.contact) && isBlank(customer.email))) {
    errors.customer = {
      contact: 'Please provide contact or email'
    }
  }

  if (isBlank(item) || isBlank(item.amount)) {
    lineItemError.amount = 'Please provide the amount'
  }

  if (isBlank(item) || isBlank(item.name)) {
    lineItemError.name = 'Please provide product/service name'
  }

  errors.line_items = [lineItemError]
  return errors
}

@connect(
  null,
  { createInvoice, appendInvoiceToList }
)
@reduxForm({
  form: 'newPaymentLink',
  validate,
  initialValues: {
    currency: 'INR',
    date: Math.ceil(new Date().getTime()/1000),
    sms_notify: true,
    email_notify: true
  }
})
export default class CreatePaymentLink extends Component {
  constructor() {
    super(...arguments)
    this.create = ::this.create
    this.state = {
      errors: null
    }
  }

  create(fieldProps) {
    let { line_items, ...props } = fieldProps
    let item = line_items[0]

    props.sms_notify = fieldProps.sms_notify ? 1 : 0
    props.email_notify = fieldProps.email_notify ? 1 : 0
    props.line_items = []
    props.line_items.push({
      name: item.name,
      amount: item.amount * 100
    })
    props.type = 'link'
    return this.props.createInvoice(props).then((response) => {
      this.props.appendInvoiceToList(response.data)
      this.props.closeModal()
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    const { handleSubmit } = this.props

    return (
      <div>
        <ModalHeader
          title='New Payment Link'
          onCloseClick={this.props.closeModal}
        />

        <Alert
          type='error'
          message={this.state.errors}
        />

        <form class='form-horizontal payment-link-form'>
          <div class='modal-body'>
            <div class='form-group customer'>
              <label class='col-md-3 control-label'>Customer</label>
              <div class='col-md-4'>
                <Field
                  name='customer[contact]'
                  component={InputField}
                  class='form-control'
                  placeholder='Customer phone'
                  autoFocus={true}
                />
              </div>

              <div class='col-md-4 or-separator'>
                <Field
                  name='customer[email]'
                  component={InputField}
                  class='form-control'
                  placeholder='Customer email'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label help-label'>
                <div>Name</div>
                <small>Product/Service</small>
              </label>
              <div class='col-md-8'>
                <Field
                  name='line_items[0][name]'
                  component={InputField}
                  class='form-control'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label help-label'>
                <div>Amount</div>
                <small>(in INR)</small>
              </label>
              <div class='col-md-8'>
                <Field
                  name='line_items[0][amount]'
                  component={InputField}
                  class='form-control'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Notify Customer</label>
              <div class='col-md-8'>
                <label class='checkbox-inline'>
                  <Field
                    name='sms_notify'
                    component='input'
                    type='checkbox'
                  />
                  SMS
                </label>
                <label class='checkbox-inline'>
                  <Field
                    name='email_notify'
                    component='input'
                    type='checkbox'
                  />
                  Email
                </label>
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
              onClick={handleSubmit(this.create)}
            />
          </div>
        </form>
      </div>
    )
  }
}
