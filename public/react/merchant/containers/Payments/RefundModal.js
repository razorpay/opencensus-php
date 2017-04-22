import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import * as NotificationsActions from 'rzp/modules/notifications'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import Alert from 'rzp/ui/Forms/Alert'
import Amount from 'rzp/ui/Amount'
import { isBlank } from 'rzp/utils/rzp-utils'
import { refundPayment, fetchPayment } from 'merchant/modules/payments/details'
import { closeModal } from 'rzp/modules/modals'

const amountValidation = (value, allValues, props) => {
  value = value || ''
  if (allValues.partial) {
    if (!value) {
      return 'Amount is required'
    }

    if (isNaN(value) || (value.split('.')[1] || []).length > 2) {
      return 'Amount can only be a Number with atmost 2 decimal places.'
    }
    if (value < 0) {
      return  `Amount can't be negative.`
    }
    if (value > (props.payment.amount - props.payment.amount_refunded)/100) {
      return `Amount can't be greater than the amount paid (${(props.payment.amount - props.payment.amount_refunded)/100}).`
    }
  }
}

const selector = formValueSelector('refundModal')
@connect(
  (state) => {
    let partial = selector(state, 'partial')
    let payable_amount = selector(state, 'amount')

    return {
      ...state.session,
      ...state.payment,
      partial,
      payable_amount
    }
  },
  { closeModal, refundPayment, fetchPayment, ...NotificationsActions }
)

@reduxForm({
  form: 'refundModal'
})

export default class RefundModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
  }

  componentWillMount() {
    let payment = this.props.payment

    this.props.initialize({
      comment: '',
      parital: false,
      amount: ((payment.amount - payment.amount_refunded)/100) + ''
    })
  }

  save = (props) => {
    this.context.confirm({
      header: 'Are you sure you want to refund this payment?',
      message: null,
      affirmativeLabel: 'Yes, Refund',
      affirmativePendingLabel: 'Refunding...',
      abortLabel: 'No, don\'t!',
      action: () => {
        let payment = this.props.payment
        let data = {
          amount: props.amount * 100,
          comment: props.comment
        }

        if (!props.partial) {
          data.amount = payment.amount - payment.amount_refunded
        }

        return this.props.refundPayment(payment, data)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Payment refunded',
              closeTimeout: 5000
            })
            this.props.fetchPayment(payment.id)
            this.props.closeModal()
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
              closeTimeout: 5000
            })
          })
      }
    }).catch(()=>{})
  }

  render() {
    const { handleSubmit, payment } = this.props

    return (
      <div>
        <ModalHeader
          title='Refund Payment'
          onCloseClick={this.props.closeModal}
        />

        <form class='form-horizontal payment-link-form' onSubmit={handleSubmit(this.save)}>
          <div class='modal-body'>
            <div class='form-group'>
              <label class='col-md-3 control-label'>
                <div>Partial Refund</div>
              </label>
              <div class='col-md-8'>
                <div class='checkbox'>
                  <label class='i-checks'>
                    <Field
                      name='partial'
                      id='partial'
                      component='input'
                      type='checkbox'
                      class='form-control'
                    />
                    <i></i>
                  </label>
                </div>
              </div>
            </div>
            {
              this.props.partial ?
              <div class='form-group'>
                <label class='col-md-3 control-label'>
                  <div>Amount</div>
                  <small>(in INR)</small>
                </label>
                <div class='col-md-8'>
                  <Field
                    name='amount'
                    component={InputField}
                    class='form-control'
                    validate={amountValidation}
                    placeholder='Enter the refund amount'
                  />
                  <i></i>
                </div>
              </div> : null
            }
            <div class='form-group'>
              <label class='col-md-3 control-label'>
                <div>Comments</div>
              </label>
              <div class='col-md-8'>
                <Field
                  name='comment'
                  component={InputField}
                  class='form-control'
                  placeholder='Add an optional comment'
                />
                <i></i>
              </div>
            </div>

            <div class='form-group'>
              <div class='col-md-8 col-md-offset-3'>
                The payment will be {this.props.partial ? 'partially ' : 'completely '}
                refunded with the refund amount set to <b>{
                  (this.props.partial ?
                  this.props.payable_amount :
                  (payment.amount - payment.amount_refunded)/100) || 0
                } INR</b>
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
              type='submit'
              class='btn btn-primary btn-rounded'
              text='Refund'
              pendingText='Refunding...'
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    )
  }
}
