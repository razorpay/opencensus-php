import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import { isBlank } from 'rzp/utils/rzp-utils'
import { saveInvoice } from 'merchant/modules/invoices/list'
import { required, phone, email } from 'rzp/utils/validators'

function validate(values) {
  let errors = {}
  let customer = values.customer
  let isNewForm = isBlank(values.line_items)

  if (!isNewForm) {
    if (isBlank(customer) || (isBlank(customer.contact) && isBlank(customer.email))) {
      errors.customer = {
        contact: 'Please provide contact or email'
      }
    }
  }

  if (values.sms_notify && (isBlank(customer) || isBlank(customer.contact))) {
    errors.customer = {
      contact: 'Please provide contact'
    }
  }

  if (values.email_notify && (isBlank(customer) || isBlank(customer.email))) {
    errors.customer = {
      email: 'Please provide email'
    }
  }

  return errors
}

@connect(
  (state) => state.session,
  { saveInvoice }
)
@reduxForm({
  form: 'newPaymentLink',
  initialValues: {
    type: 'link'
  },
  validate
})
export default class CreatePaymentLink extends Component {
  static contextTypes = {
    session: PropTypes.object
  }

  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
  }

  componentWillMount() {
    if (this.props.invoice) {
      this.props.initialize(this.props.invoice)
    }
  }

  save = (props) => {
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
    let isTestMode = this.props.mode === 'test'
    let isNewForm =  !(invoice && !isBlank(invoice.line_items))
    let isEdit = !!invoice

    return (
      <div>
        <ModalHeader
          title={isEdit ? 'Edit Payment Link' : 'Create Payment Link'}
          onCloseClick={this.props.closeModal}
        />

        <Alert
          type='error'
          message={this.state.errors}
        />

        <form class='form-horizontal payment-link-form' onSubmit={handleSubmit(this.save)}>
          <div class='modal-body'>
            {
              isNewForm &&
              <div>
                <div class='form-group'>
                  <label class='col-md-3 control-label help-label label-required'>
                    <div>Amount</div>
                    <small>(in INR)</small>
                  </label>
                  <div class='col-md-8'>
                    <Field
                      name='amountInINR'
                      component={InputField}
                      class='form-control'
                      autoFocus={true}
                      validate={required('Please provide the amount')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <div class='form-group'>
                  <label class='col-md-3 control-label help-label label-required'>
                    Summary
                  </label>
                  <div class='col-md-8'>
                    <Field
                      name='description'
                      component={InputField}
                      tagName='textarea'
                      type='textarea'
                      class='form-control'
                      validate={required('Please provide the description')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <div class='form-group'>
                  <label class='col-md-3 control-label help-label'>
                    Receipt No.
                  </label>
                  <div class='col-md-8'>
                    <Field
                      name='receipt'
                      component='input'
                      class='form-control'
                    />
                  </div>
                </div>
              </div>
            }

            <div class='form-group customer'>
              <label class='col-md-3 control-label'>Customer</label>
              <div class='col-md-4'>
                <Field
                  name='customer[contact]'
                  component={InputField}
                  class='form-control'
                  placeholder='Customer phone'
                  validate={phone('Please provide valid contact number')}
                  disabled={isEdit}
                />
              </div>

              <div class='col-md-4 or-separator'>
                <Field
                  name='customer[email]'
                  component={InputField}
                  class='form-control'
                  placeholder='Customer email'
                  validate={email('Please provide valid email')}
                  disabled={isEdit}
                />
              </div>
            </div>

            {
              !isNewForm &&
              <div>
                <div class='form-group'>
                  <label class='col-md-3 control-label help-label'>
                    <div>Item Name</div>
                    <small>Product/Service</small>
                  </label>
                  <div class='col-md-8'>
                    <Field
                      name='line_items[0][name]'
                      component={InputField}
                      class='form-control'
                      validate={required('Please provide product/service name')}
                      disabled={isEdit}
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
                      validate={required('Please provide the amount')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <div class='form-group'>
                  <label class='col-md-3 control-label help-label'>
                    Receipt No.
                  </label>
                  <div class='col-md-8'>
                    <Field
                      name='receipt'
                      component='input'
                      class='form-control'
                    />
                  </div>
                </div>
              </div>
            }

            <div class='form-group'>
              <label class='col-md-3 control-label'>Notify Customer</label>
              <div class='col-md-8'>
                <label class='checkbox-inline'>
                  <Field
                    name='sms_notify'
                    component='input'
                    type='checkbox'
                    disabled={isEdit}
                  />
                  SMS
                </label>
                <label class='checkbox-inline'>
                  <Field
                    name='email_notify'
                    component='input'
                    type='checkbox'
                    disabled={isEdit}
                  />
                  Email
                </label>
              </div>
            </div>
            {
              isTestMode &&
              <div class='row'>
                <div class='col-md-8 col-md-offset-3'>
                  <div class='alert alert-sm alert-warning'>
                    You are creating the link in <b>Test Mode</b>. So, only test payments can be made for this link.
                    Also, SMS will not be sent in test mode
                  </div>
                </div>
              </div>
            }
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
              type='submit'
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
