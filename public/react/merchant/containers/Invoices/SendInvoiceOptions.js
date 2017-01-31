import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import { saveInvoice } from 'merchant/modules/invoices/list'
import * as ModalActions from 'merchant/modules/modals'
import Invoice from 'merchant/models/Invoice'

function validate(values) {
  let errors = {}
  return errors
}

@connect(
  (state) => state.session,
  { ...ModalActions, saveInvoice }
)
@reduxForm({
  form: 'newInvoice',
  validate
})
export default class SendInvoiceOptions extends Component {
  constructor() {
    super(...arguments)
    this.save = ::this.save
    this.state = {
      errors: null
    }
  }

  componentWillMount() {
    if (this.props.invoice) {
      this.props.initialize(new Invoice({
        ...this.props.invoice,
        sms_notify: false,
        email_notify: false
      }))
    }
  }

  save(props) {
    return this.props.saveInvoice(props).then((invoice) => {
      this.props.onSave(invoice)
      this.props.closeModal()
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    const { handleSubmit, invoice } = this.props
    let isLiveMode = this.props.mode === 'live'

    return (
      <div>
        <Alert
          type='error'
          message={this.state.errors}
        />

        <form class='form-horizontal' onSubmit={handleSubmit(this.save)}>
          <div class='modal-body'>
            <h4>SENDING OPTIONS</h4>
            {
              invoice.customer_details.customer_contact ?
                <div class='checkbox'>
                  <label>
                    <Field
                      name='sms_notify'
                      component='input'
                      type='checkbox'
                    />
                    Send SMS to {invoice.customer_details.customer_contact}
                  </label>
                </div> : ''
            }

            {
              invoice.customer_details.customer_contact ?
                <div class='checkbox'>
                  <label>
                    <Field
                      name='email_notify'
                      component='input'
                      type='checkbox'
                    />
                    Send email to
                  </label>
                </div> : ''
            }
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
              type='submit'
              class='btn btn-primary'
              text='Send'
              pendingText='Sending...'
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    )
  }
}
