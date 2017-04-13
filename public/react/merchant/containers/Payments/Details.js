import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import PaymentDetails from 'merchant/components/Payments/PaymentDetails'
import * as PaymentActions from 'merchant/modules/payments/details'

@connect(
  (state) => state.payment,
  PaymentActions
)
export default class PaymentDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchPayment(this.props.id)
  }

  fetchCardDetails = (payment) => {
    return this.props.fetchCardDetails(payment)
  }

  fetchRefunds = (payment) => {
    return this.props.fetchRefunds(payment)
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

    console.log(this.props.payment)

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
          />
        </div>
      </div>
    )
  }
}
