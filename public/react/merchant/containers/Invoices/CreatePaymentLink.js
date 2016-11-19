import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import { isBlank } from 'rzp/utils/rzp-utils'
import { createInvoice, appendInvoiceToList } from 'merchant/modules/invoices'

@connect(
  null,
  { createInvoice, appendInvoiceToList }
)
@reduxForm({
  form: 'newPaymentLink'
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
    fieldProps.date = Math.ceil(new Date().getTime()/1000)
    fieldProps.currency = 'INR'
    fieldProps.line_items[0].amount = fieldProps.line_items[0].amount * 100

    return this.props.createInvoice(fieldProps).then((response) => {
      this.props.appendInvoiceToList(response.data)
      this.props.closeModal()
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    const { handleSubmit, isNew } = this.props

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

        <form class='form-horizontal'>
          <div class='modal-body'>
            <div class='form-group'>
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

              <div class='col-md-4'>
                <Field
                  name='customer[email]'
                  component={InputField}
                  class='form-control'
                  placeholder='Customer email'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Product/Service Name</label>
              <div class='col-md-8'>
                <Field
                  name='line_items[0][name]'
                  component={InputField}
                  class='form-control'
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Amount</label>
              <div class='col-md-8'>
                <Field
                  name='line_items[0][amount]'
                  component={InputField}
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
              onClick={handleSubmit(this.create)}
            />
          </div>
        </form>
      </div>
    )
  }
}
