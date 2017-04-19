import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import Amount from 'rzp/ui/Amount'
import PaymentDetails from 'merchant/components/Payments/PaymentDetails'
import * as NotificationsActions from 'merchant/modules/notifications'
import * as PaymentActions from 'merchant/modules/payments/details'
import * as ModalActions from 'merchant/modules/modals'
import RefundModal from './RefundModal'

@connect(
  (state) => state.payment,
  { ...ModalActions, ...PaymentActions, ...NotificationsActions }
)
export default class PaymentDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func
  }

  componentWillMount() {
    this.props.fetchPayment(this.props.id)
  }

  fetchCardDetails = (payment) => {
    return this.props.fetchCardDetails(payment)
  }

  fetchRefunds = (payment) => {
    return this.props.fetchRefunds(payment)
  }

  confirmCapture = (payment) => {
    this.context.confirm({
      header: 'Are you sure you want to capture this payment?',
      message: () => (
        <div class='text-semi-muted'>
          <p>The payment amount is <b><Amount value={payment.amount} /></b></p>
        </div>
      ),
      affirmativeLabel: 'Yes, Capture',
      affirmativePendingLabel: 'Capturing...',
      abortLabel: 'No, don\'t!',
      action: () => {
        this.props.capturePayment(payment).then(() => {
          this.props.showNotification({
            type: 'success',
            message: 'Payment Captured',
            closeTimeout: 5000
          })
        }).catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
            closeTimeout: 5000
          })
        })
      }
    }).catch(()=>{})
  }

  openRefundModal = (payment) => {
    this.props.openModal({
      component: <RefundModal
        payment={payment}
      />
    })
  }

  render() {
    let {
      loading,
      error,
      payment,
      card,
      refunds
    } = this.props
    let statusMsg = {}

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error
      }
    }

    return (
      <div class='react-root'>
        <Header title='Payment Details' />

        <div class='content-wrapper'>
          <PaymentDetails
            payment={payment}
            card={card}
            refunds={refunds}
            isLoading={loading}
            statusMsg={statusMsg}
            onToggleCardDetails={this.fetchCardDetails}
            onToggleRefundList={this.fetchRefunds}
            confirmCapture={this.confirmCapture}
            openRefundModal={this.openRefundModal}
          />
        </div>
      </div>
    )
  }
}
